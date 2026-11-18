<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockDisposal;
use App\Models\StockInItem;
use App\Models\StockIn;
use App\Services\StockDisposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockDisposalController extends Controller
{
    private function canManage(): bool { return auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.manage'); }
    private function canApprove(): bool { return auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.approve'); }
    private function canView(): bool { return $this->canManage() || $this->canApprove() || auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.view'); }
    private function canResolve(): bool { return auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.resolve'); }

    private function assertLocationScope(int $locationId): void
    {
        $user = auth()->user();
        if ($user->isWarehouseStaff()) abort_unless(in_array($locationId, $user->assignedWarehouseLocationIds(), true), 403);
        elseif ($user->hasRole('pic_outlet')) abort_unless($user->canAccessOutletLocation($locationId), 403);
    }

    public function index(): View
    {
        abort_unless($this->canView(), 403);
        $disposals = StockDisposal::with(['location', 'creator', 'reviewer', 'items.product.unit', 'items.batch'])
            ->when(auth()->user()->isLocationScopedUser(), fn ($query) => $query->whereIn('location_id', auth()->user()->assignedLocationIds()))
            ->latest()->paginate(15);
        return view('inventory.stock-disposals.index', compact('disposals'));
    }

    public function create(): View
    {
        abort_unless($this->canManage(), 403);
        $user = auth()->user();
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        if ($user->isLocationScopedUser()) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        return view('inventory.stock-disposals.create', [
            'locations' => Location::where('is_active', true)->when($user->isLocationScopedUser(), fn ($query) => $query->whereIn('id', $locationIds))->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'invoices' => PurchaseInvoice::with('supplier')->where('status', 'accepted')->whereNotNull('purchase_order_id')
                ->when($user->isWarehouseStaff(), fn ($query) => $query->whereHas('order', fn ($orders) => $orders->whereIn('location_id', $locationIds)))
                ->latest()->get(),
            'batches' => StockInItem::with(['stockIn.location', 'product.unit'])->whereHas('stockIn', fn ($query) => $query->where('status', 'approved')->when($user->isLocationScopedUser(), fn ($sub) => $sub->whereIn('location_id', $locationIds)))->latest()->get(),
        ]);
    }

    public function store(Request $request, StockDisposalService $service): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_disposals', 'reference')],
            'type' => ['required', Rule::in(['write_off', 'supplier_return'])],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'purchase_invoice_id' => ['required_if:type,supplier_return', 'nullable', 'integer', 'exists:purchase_invoices,id'],
            'reason_category' => ['required', Rule::in(['expired', 'damaged', 'lost', 'quality_rejected', 'supplier_return', 'other'])],
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
            'product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'batch_id' => ['nullable', 'integer', 'exists:stock_in_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
        $this->assertLocationScope((int) $data['location_id']);
        if (auth()->user()->isWarehouseStaff() && ! empty($data['purchase_invoice_id'])) {
            abort_unless(PurchaseInvoice::whereKey($data['purchase_invoice_id'])
                ->whereHas('order', fn ($orders) => $orders->whereIn('location_id', auth()->user()->assignedWarehouseLocationIds()))
                ->exists(), 403);
        }

        if (! empty($data['batch_id'])) {
            $batch = StockInItem::with('stockIn')->findOrFail($data['batch_id']);
            if ((int) $batch->product_id !== (int) $data['product_id']
                || (int) $batch->stockIn->location_id !== (int) $data['location_id']) {
                throw ValidationException::withMessages(['batch_id' => 'Batch harus berasal dari produk dan lokasi yang dipilih.']);
            }
        }
        if ($data['type'] === 'supplier_return') {
            if ($data['reason_category'] !== 'supplier_return' || empty($data['batch_id'])) {
                throw ValidationException::withMessages(['type' => 'Retur supplier wajib memakai kategori retur dan batch penerimaan asal.']);
            }
            $invoice = PurchaseInvoice::with('items')->findOrFail($data['purchase_invoice_id']);
            $batch ??= StockInItem::with('stockIn')->findOrFail($data['batch_id']);
            if ($invoice->status !== 'accepted'
                || ! $invoice->purchase_order_id
                || (int) $batch->stockIn->purchase_order_id !== (int) $invoice->purchase_order_id
                || ! $invoice->items->contains('product_id', (int) $data['product_id'])) {
                throw ValidationException::withMessages(['purchase_invoice_id' => 'Retur harus terkait dengan invoice Finance yang diterima dan batch penerimaan dari PO invoice tersebut.']);
            }
        } elseif ($data['reason_category'] === 'supplier_return') {
            throw ValidationException::withMessages(['reason_category' => 'Kategori retur supplier hanya digunakan untuk jenis transaksi retur supplier.']);
        }

        $disposal = $service->submit($data);
        return redirect()->route('inventory.stock-disposals.show', $disposal)->with('status', 'Pengajuan tersimpan dan jumlahnya dikarantina. Saldo fisik berubah setelah disetujui.');
    }

    public function show(StockDisposal $stockDisposal): View
    {
        abort_unless($this->canView(), 403);
        $disposal = $stockDisposal->load(['location', 'invoice', 'creator', 'reviewer', 'items.product.unit', 'items.batch.stockIn', 'items.supplierResolutions.recorder', 'items.supplierResolutions.replacementReceipt']);
        $this->assertLocationScope((int) $disposal->location_id);
        $replacementReceipts = collect();
        if ($disposal->type === 'supplier_return' && $disposal->invoice) {
            $productIds = $disposal->items->pluck('product_id');
            $replacementReceipts = StockIn::with('items.product')
                ->where('status', 'approved')->where('location_id', $disposal->location_id)
                ->where('supplier_id', $disposal->invoice->supplier_id)
                ->whereHas('items', fn ($query) => $query->whereIn('product_id', $productIds))
                ->latest('received_at')->get();
        }
        return view('inventory.stock-disposals.show', [
            'disposal' => $disposal,
            'canApprove' => $this->canApprove() && (int) $stockDisposal->created_by !== (int) auth()->id(),
            'canResolve' => $this->canResolve(),
            'replacementReceipts' => $replacementReceipts,
        ]);
    }

    public function resolveSupplier(Request $request, StockDisposal $stockDisposal, StockDisposalService $service): RedirectResponse
    {
        abort_unless($this->canResolve(), 403);
        $this->assertLocationScope((int) $stockDisposal->location_id);
        $data = $request->validate([
            'stock_disposal_item_id' => ['required', 'integer', 'exists:stock_disposal_items,id'],
            'resolution_type' => ['required', Rule::in(['credit_note', 'refund', 'replacement', 'claim_rejected'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'amount' => ['nullable', 'numeric', 'gt:0', 'max:999999999999.99', 'required_if:resolution_type,credit_note,refund'],
            'reference' => ['nullable', 'string', 'max:150', 'required_unless:resolution_type,replacement'],
            'replacement_stock_in_id' => ['nullable', 'integer', 'exists:stock_ins,id', 'required_if:resolution_type,replacement'],
            'resolved_at' => ['nullable', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $service->recordSupplierResolution($stockDisposal, $data);
        return back()->with('status', 'Penyelesaian supplier tersimpan dan masuk riwayat audit.');
    }

    public function approve(Request $request, StockDisposal $stockDisposal, StockDisposalService $service): RedirectResponse
    {
        abort_unless($this->canApprove(), 403);
        $this->assertLocationScope((int) $stockDisposal->location_id);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:5000']]);
        $service->approve($stockDisposal, $data['review_note'] ?? null);
        return back()->with('status', 'Pengajuan disetujui dan pengurangan stok dicatat.');
    }

    public function reject(Request $request, StockDisposal $stockDisposal, StockDisposalService $service): RedirectResponse
    {
        abort_unless($this->canApprove(), 403);
        $this->assertLocationScope((int) $stockDisposal->location_id);
        $data = $request->validate(['review_note' => ['required', 'string', 'min:3', 'max:5000']]);
        $service->reject($stockDisposal, $data['review_note']);
        return back()->with('status', 'Pengajuan ditolak. Saldo tidak berubah.');
    }
}
