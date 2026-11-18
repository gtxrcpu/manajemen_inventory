<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Services\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Str;

class PurchasingController extends Controller
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage') || auth()->user()->hasPermissionTo('stock.manage'), 403);
    }

    private function assertWarehouseStaffScope(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();
        if ($user->isWarehouseStaff()) {
            abort_unless(in_array((int) $purchaseOrder->location_id, $user->assignedWarehouseLocationIds(), true), 403);
        }
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (auth()->user()->hasRole('staff_gudang')) {
            return redirect()->route('inventory.stock-in.purchase-orders');
        }
        $this->authorizeView();

        $prQuery = PurchaseRequest::with(['requester', 'items']);
        $poQuery = PurchaseOrder::with(['request', 'supplier', 'location', 'items', 'stockIn.items']);

        // Search & filter
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $prQuery->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%{$q}%"));
            });
            $poQuery->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('location', fn ($location) => $location->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%{$q}%"));
            });
        }
        $prStatus = $request->query('pr_status');
        if (in_array($prStatus, ['pending_approval', 'approved', 'rejected'], true)) {
            $prQuery->where('status', $prStatus);
        }
        $poStatus = $request->query('po_status');
        if ($poStatus === 'active') {
            $poQuery->whereIn('status', ['ordered', 'issued']);
        } elseif ($poStatus === 'follow_up') {
            $poQuery->where('status', 'issued')->whereHas('stockIn');
        } elseif (in_array($poStatus, ['ordered', 'issued', 'received', 'closed_with_variance'], true)) {
            $poQuery->where('status', $poStatus);
        }

        // Stats
        $stats = [
            'pr_total'    => PurchaseRequest::count(),
            'pr_approved' => PurchaseRequest::where('status', 'approved')->count(),
            'po_total'    => PurchaseOrder::count(),
            'po_pending'  => PurchaseOrder::whereIn('status', ['ordered', 'issued'])->count(),
            'po_follow_up' => PurchaseOrder::where('status', 'issued')->whereHas('stockIn')->count(),
            'po_received' => PurchaseOrder::where('status', 'received')->count(),
            'po_closed_with_variance' => PurchaseOrder::where('status', 'closed_with_variance')->count(),
        ];

        return view('purchasing.index', [
            'requests' => $prQuery->latest()->paginate(10, ['*'], 'requests_page')->withQueryString(),
            'orders'   => $poQuery->latest()->paginate(10, ['*'], 'orders_page')->withQueryString(),
            'stats'    => $stats,
        ]);
    }

    public function recordSupplierFollowUp(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $request->validate([
            'action' => ['required', Rule::in(['awaiting_delivery', 'replacement_expected', 'credit_note_expected', 'refund_expected', 'close_shortage'])],
            'due_at' => ['nullable', 'date'],
            'note' => ['required', 'string', 'min:5', 'max:5000'],
        ]);
        if (in_array($data['action'], ['awaiting_delivery', 'replacement_expected', 'credit_note_expected', 'refund_expected'], true) && empty($data['due_at'])) {
            return back()->withErrors(['due_at' => 'Isi tanggal janji supplier atau tanggal Purchasing akan menagih kembali.'])->withInput();
        }

        $order = $service->recordSupplierFollowUp($purchaseOrder, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', $order->status === 'closed_with_variance'
            ? 'PO ditutup dengan selisih. Catatan Purchasing tersimpan untuk rekonsiliasi invoice.'
            : 'Tindak lanjut supplier tersimpan. PO tetap terbuka untuk penerimaan berikutnya.');
    }

    public function createRequest(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        return view('purchasing.request-create', [
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'productOptions' => Product::where('is_active', true)->with('unit')->orderBy('name')->get()->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
        ]);
    }

    public function storeRequest(Request $request, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('purchase_requests', 'reference')],
            'required_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
        $pr = $service->createRequest($data);
        return redirect()->route('purchasing.requests.show', $pr)->with('status', 'Purchase Request disetujui otomatis dan siap diproses oleh Purchasing.');
    }

    public function showRequest(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $this->authorizeView();
        return view('purchasing.request-show', ['purchaseRequest' => $purchaseRequest->load(['requester', 'reviewer', 'items.product.unit', 'purchaseOrder'])]);
    }

    public function createOrder(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        abort_unless($purchaseRequest->status === 'approved' && ! $purchaseRequest->purchaseOrder()->exists(), 404);
        return view('purchasing.order-create', [
            'purchaseRequest' => $purchaseRequest->load('items.product.unit'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeOrder(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('purchase_orders', 'reference')],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->whereNull('deleted_at')],
            'expected_delivery' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = $service->createOrder($purchaseRequest, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Purchase Order berhasil diterbitkan.');
    }

    public function showOrder(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeView();
        $this->assertWarehouseStaffScope($purchaseOrder);
        $purchaseOrder->load(['request', 'supplier', 'location', 'creator', 'supplierFollowUpBy', 'items.product.unit', 'stockIn.items.product.unit', 'stockIn.evidences', 'invoices.items.product.unit']);
        $invoice = $purchaseOrder->invoices->sortByDesc('created_at')->first();
        $invoiceQuantities = $invoice?->items->keyBy('product_id')->map(fn ($line) => (float) $line->quantity) ?? collect();
        $receivedQuantities = $purchaseOrder->stockIn
            ->flatMap(fn ($receipt) => $receipt->items)
            ->filter(fn ($line) => $line->purchase_order_item_id !== null)
            ->groupBy('purchase_order_item_id')
            ->map(fn ($lines) => (float) $lines->sum('quantity'));

        return view('purchasing.order-show', [
            'order' => $purchaseOrder,
            'invoice' => $invoice,
            'invoiceQuantities' => $invoiceQuantities,
            'receivedQuantities' => $receivedQuantities,
            'receiptSubmissionKey' => (string) Str::uuid(),
        ]);
    }

    public function receivingOrders(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('stock.manage'), 403);
        $warehouseLocationIds = $request->user()->isWarehouseStaff() ? $request->user()->assignedWarehouseLocationIds() : [];
        if ($request->user()->isWarehouseStaff()) abort_unless($warehouseLocationIds, 403, 'Akun Staff Gudang belum memiliki gudang aktif yang ditugaskan.');
        $query = PurchaseOrder::with(['supplier', 'location', 'items.product.unit', 'invoices.items'])
            ->where('status', 'issued')
            ->when($request->user()->isWarehouseStaff(), fn ($orders) => $orders->whereIn('location_id', $warehouseLocationIds))
            ->latest();
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(fn ($orders) => $orders->where('reference', 'like', '%'.$term.'%')
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$term.'%'))
                ->orWhereHas('invoices', fn ($invoice) => $invoice->where('invoice_number', 'like', '%'.$term.'%')));
        }

        return view('inventory.stock-in.purchase-orders', ['orders' => $query->paginate(12)->withQueryString()]);
    }

    public function receiveOrder(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('stock.manage'), 403);
        $this->assertWarehouseStaffScope($purchaseOrder);
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'received_at' => ['required', 'date'],
            'purchase_invoice_id' => ['nullable', 'integer', 'exists:purchase_invoices,id'],
            'evidence_photos' => ['required', 'array', 'min:1', 'max:5'],
            'evidence_photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.delivered_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'items.*.accepted_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'items.*.variance_note' => ['nullable', 'string', 'max:1000'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_at'],
        ], [
            'evidence_photos.required' => 'Unggah minimal satu foto bukti kedatangan supplier.',
            'evidence_photos.min' => 'Unggah minimal satu foto bukti kedatangan supplier.',
            'evidence_photos.max' => 'Maksimal lima foto untuk satu penerimaan.',
            'evidence_photos.*.image' => 'Bukti kedatangan harus berupa file gambar.',
            'evidence_photos.*.mimes' => 'Gunakan foto berformat JPG, PNG, atau WebP.',
            'evidence_photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ]);
        $receipt = $service->receiveOrder(
            $purchaseOrder,
            isset($data['purchase_invoice_id']) ? (int) $data['purchase_invoice_id'] : null,
            $data['received_at'],
            $data['items'] ?? [],
            $data['idempotency_key'],
            $request->file('evidence_photos', []),
        );
        $statusMessage = $receipt->purchase_invoice_id
            ? 'Penerimaan berhasil disimpan. Stok gudang sudah diperbarui.'
            : 'Penerimaan berhasil disimpan. Stok gudang sudah diperbarui; invoice masih perlu ditautkan.';
        return redirect()->route('purchasing.orders.show', $purchaseOrder)
            ->with('status', $statusMessage)
            ->with('receipt_reference', $receipt->reference)
            ->with('receipt_url', route('inventory.stock-in.show', $receipt));
    }
}
