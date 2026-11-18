<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInEvidence;
use App\Models\Supplier;
use App\Services\StockInApprovalService;
use App\Services\StockInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockInController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $user->hasPermissionTo('stock.manage');
        $canApprove = $user->hasPermissionTo('stock-in.approve');
        abort_unless($canManage || $canApprove, 403);
        $isPic = $user->hasRole('pic_outlet');
        $isWarehouseStaff = $user->isWarehouseStaff();
        $isLocationScoped = $user->isLocationScopedUser();
        $locationIds = $isLocationScoped ? $user->assignedLocationIds() : [];
        if ($isLocationScoped) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $locationId = $isPic
            ? ($request->filled('location_id') ? $request->integer('location_id') : null)
            : ($request->filled('location_id') ? $request->integer('location_id') : $request->session()->get('selected_location_id'));
        if ($isLocationScoped && $locationId) abort_unless(in_array($locationId, $locationIds, true), 403);
        $query = StockIn::with(['supplier', 'location', 'receiver', 'decisionMaker', 'items.product.unit']);

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('source', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
            });
        }

        if ($isLocationScoped) $query->when($locationId, fn ($q) => $q->where('location_id', $locationId), fn ($q) => $q->whereIn('location_id', $locationIds));
        elseif ($locationId) $query->where('location_id', $locationId);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->input('date_to'));
        }

        $receipts = $query->latest('received_at')->latest('id')->paginate(15)->withQueryString();

        $scopeLocation = fn ($q) => $isLocationScoped
            ? ($locationId ? $q->where('location_id', $locationId) : $q->whereIn('location_id', $locationIds))
            : $q->when($locationId, fn ($scoped) => $scoped->where('location_id', $locationId));
        $stats = [
            'total_receipts' => $scopeLocation(StockIn::query())->count(),
            'total_items_qty' => (float) \App\Models\StockInItem::whereHas('stockIn', fn ($q) => $scopeLocation($q->where('status', 'approved')))->sum('quantity'),
            'this_month_count' => $scopeLocation(StockIn::whereMonth('received_at', now()->month)->whereYear('received_at', now()->year))->count(),
            'suppliers_count' => Supplier::where('is_active', true)->count(),
        ];

        return view('inventory.stock-in.index', [
            'receipts' => $receipts,
            'stats' => $stats,
            'locations' => Location::where('is_active', true)->when($isLocationScoped, fn ($q) => $q->whereIn('id', $locationIds))->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'filters' => array_merge($request->only(['q', 'supplier_id', 'date_from', 'date_to']), ['location_id' => $locationId]),
            'canManage' => $canManage,
            'canApprove' => $canApprove,
            'isWarehouseStaff' => $isWarehouseStaff,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermissionTo('stock.manage'), 403);
        $user = $request->user();
        abort_if($user->isWarehouseStaff(), 403, 'Penerimaan barang gudang harus melalui Validasi Penerimaan dari Purchase Order.');
        $isLocationScoped = $user->isLocationScopedUser();
        $locationIds = $isLocationScoped ? $user->assignedLocationIds() : [];
        if ($isLocationScoped) abort_unless($locationIds, 403, 'Akun operasional belum memiliki lokasi aktif yang ditugaskan.');
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('inventory.stock-in.create', [
            'products' => $products,
            'productOptions' => $products
                ->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'locations' => Location::where('is_active', true)->when($isLocationScoped, fn ($query) => $query->whereIn('id', $locationIds))->orderBy('name')->get(),
            'selectedLocationId' => $isLocationScoped ? ($locationIds[0] ?? null) : $request->session()->get('selected_location_id'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockInService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('stock.manage'), 403);
        abort_if($request->user()->isWarehouseStaff(), 403, 'Penerimaan barang gudang harus melalui Validasi Penerimaan dari Purchase Order.');
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_ins', 'reference')],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'source' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_at'],
        ], [
            'reference.unique' => 'Nomor referensi ini sudah pernah digunakan.',
            'items.required' => 'Tambahkan minimal satu produk yang diterima.',
            'items.*.quantity.gt' => 'Jumlah barang harus lebih dari nol.',
        ]);
        if ($request->user()->isWarehouseStaff()) {
            abort_unless(in_array((int) $data['location_id'], $request->user()->assignedWarehouseLocationIds(), true), 403);
        } elseif ($request->user()->hasRole('pic_outlet')) {
            abort_unless($request->user()->canAccessOutletLocation((int) $data['location_id']), 403);
        }

        $trackedProducts = Product::whereIn('id', collect($data['items'])->pluck('product_id')->unique())
            ->where('requires_expiry_tracking', true)->pluck('id')->all();
        foreach ($data['items'] as $index => $line) {
            if (in_array((int) $line['product_id'], $trackedProducts, true)
                && (blank($line['batch_number'] ?? null) || blank($line['expiry_date'] ?? null))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}.batch_number" => 'Produk ini wajib dicatat nomor batch dan tanggal kedaluwarsanya.',
                ]);
            }
        }

        $location = Location::whereKey($data['location_id'])->firstOrFail();
        $requiresApproval = $location->type === Location::TYPE_OUTLET;
        $receipt = $service->receive($data, $requiresApproval);

        $message = $requiresApproval
            ? 'Stock In tersimpan dan menunggu persetujuan PIC outlet. Saldo belum berubah.'
            : 'Stock In berhasil dicatat dan saldo sudah diperbarui.';
        return redirect()->route('inventory.stock-in.show', $receipt)->with('status', $message);
    }

    public function show(Request $request, StockIn $stockIn): View
    {
        $user = $request->user();
        $canManage = $user->hasPermissionTo('stock.manage');
        $assignedPic = $user->hasRole('pic_outlet') && $user->canAccessOutletLocation((int) $stockIn->location_id);
        if ($user->isWarehouseStaff()) abort_unless(in_array((int) $stockIn->location_id, $user->assignedWarehouseLocationIds(), true), 403);
        $adminReviewer = $user->hasRole('super_admin') && $user->hasPermissionTo('stock-in.approve');
        // Purchasing may inspect PO-linked receipts while matching an invoice,
        // but this read access does not grant approval or stock management.
        $canViewForInvoice = $user->hasPermissionTo('invoice.view') && $stockIn->purchase_order_id !== null;
        abort_unless($canManage || $adminReviewer || ($user->hasPermissionTo('stock-in.approve') && $assignedPic) || $canViewForInvoice, 403);

        return view('inventory.stock-in.show', [
            'receipt' => $stockIn->load(['supplier', 'purchaseInvoice', 'location', 'receiver', 'decisionMaker', 'items.product.unit']),
            'canApprove' => $user->hasPermissionTo('stock-in.approve') && ($assignedPic || $adminReviewer),
        ]);
    }

    public function evidence(Request $request, StockIn $stockIn, StockInEvidence $evidence)
    {
        abort_unless((int) $evidence->stock_in_id === (int) $stockIn->id, 404);

        $user = $request->user();
        $assignedPic = $user->hasRole('pic_outlet') && $user->canAccessOutletLocation((int) $stockIn->location_id);
        $assignedWarehouseStaff = $user->isWarehouseStaff()
            && in_array((int) $stockIn->location_id, $user->assignedWarehouseLocationIds(), true);
        $canManage = $user->hasPermissionTo('stock.manage') && (! $user->isWarehouseStaff() || $assignedWarehouseStaff);
        $adminReviewer = $user->hasRole('super_admin') && $user->hasPermissionTo('stock-in.approve');
        $canViewPurchasing = $stockIn->purchase_order_id !== null && $user->hasPermissionTo('purchasing.manage');
        $canViewInvoiceEvidence = $stockIn->purchase_order_id !== null
            && ($user->hasPermissionTo('invoice.view') || $user->hasPermissionTo('invoice.review'));

        abort_unless(
            $canManage
            || $adminReviewer
            || ($user->hasPermissionTo('stock-in.approve') && $assignedPic)
            || $canViewPurchasing
            || $canViewInvoiceEvidence,
            403,
        );
        abort_unless(Storage::disk('local')->exists($evidence->file_path), 404);

        $extension = match ($evidence->mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'img',
        };

        return Storage::disk('local')->response(
            $evidence->file_path,
            'bukti-kedatangan-'.$evidence->id.'.'.$extension,
            ['Content-Type' => $evidence->mime_type, 'X-Content-Type-Options' => 'nosniff'],
            'inline',
        );
    }

    public function approve(Request $request, StockIn $stockIn, StockInApprovalService $service): RedirectResponse
    {
        $this->assertCanReview($request, $stockIn);
        $service->approve($stockIn);
        return redirect()->route('inventory.stock-in.show', $stockIn)->with('status', 'Stock In disetujui dan saldo outlet sudah diperbarui.');
    }

    public function reject(Request $request, StockIn $stockIn, StockInApprovalService $service): RedirectResponse
    {
        $this->assertCanReview($request, $stockIn);
        $data = $request->validate([
            'rejection_category' => ['required', Rule::in(['quantity_mismatch', 'condition', 'document', 'other'])],
            'rejection_reason' => ['required', 'string', 'min:3', 'max:5000'],
        ]);
        $service->reject($stockIn, $data['rejection_category'], $data['rejection_reason']);
        return redirect()->route('inventory.stock-in.show', $stockIn)->with('status', 'Stock In ditolak. Saldo outlet tidak berubah.');
    }

    private function assertCanReview(Request $request, StockIn $stockIn): void
    {
        $user = $request->user();
        $assignedPic = $user->hasRole('pic_outlet') && $user->canAccessOutletLocation((int) $stockIn->location_id);
        $adminReviewer = $user->hasRole('super_admin');
        abort_unless($user->hasPermissionTo('stock-in.approve') && ($assignedPic || $adminReviewer), 403);
    }
}
