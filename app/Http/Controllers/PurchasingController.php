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
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage') || auth()->user()->hasPermissionTo('stock.manage') || auth()->user()->hasPermissionTo('purchase-request.approve'), 403);
    }

    private function assertReceivingLocationScope(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();
        if ($user->isWarehouseStaff()) {
            abort_unless(in_array((int) $purchaseOrder->location_id, $user->assignedWarehouseLocationIds(), true), 403);
        } elseif ($user->hasRole('staff_outlet')) {
            abort_unless(in_array((int) $purchaseOrder->location_id, $user->assignedOutletLocationIds(), true), 403);
        }
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (auth()->user()->hasRole('staff_gudang') || auth()->user()->hasRole('staff_outlet')) {
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
            $poQuery->whereIn('status', ['ordered', 'issued', 'open', 'partially_received']);
        } elseif ($poStatus === 'follow_up') {
            $poQuery->whereIn('status', ['issued', 'open', 'partially_received'])->whereHas('stockIn');
        } elseif (in_array($poStatus, ['ordered', 'issued', 'open', 'partially_received', 'received', 'completed', 'closed_with_variance', 'draft', 'cancelled'], true)) {
            $poQuery->where('status', $poStatus);
        }

        // Stats
        $stats = [
            'pr_total'    => PurchaseRequest::count(),
            'pr_approved' => PurchaseRequest::where('status', 'approved')->count(),
            'po_total'    => PurchaseOrder::count(),
            'po_pending'  => PurchaseOrder::whereIn('status', ['ordered', 'issued', 'open', 'partially_received'])->count(),
            'po_follow_up' => PurchaseOrder::whereIn('status', ['issued', 'open', 'partially_received'])->whereHas('stockIn')->count(),
            'po_received' => PurchaseOrder::whereIn('status', ['received', 'completed'])->count(),
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
            'locations' => Location::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'purchaseRequest' => null,
            'initialItems' => session()->getOldInput('items', []),
        ]);
    }

    public function estimateRequestItem(Request $request, PurchasingService $service)
    {
        abort_unless($request->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->whereIn('type', [Location::TYPE_WAREHOUSE, Location::TYPE_OUTLET])->where('is_active', true)->whereNull('deleted_at')],
        ]);
        $product = Product::with('unit')->findOrFail($data['product_id']);
        $location = Location::findOrFail($data['location_id']);

        return response()->json($service->requestItemEstimate($product, $location));
    }

    public function storeRequest(Request $request, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $this->validatePurchaseRequest($request);
        $pr = $service->createRequest($data);
        return redirect()->route('purchasing.requests.show', $pr)->with('status', 'Purchase Request terkirim dan menunggu persetujuan Management.');
    }

    public function editRequest(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        abort_unless($purchaseRequest->status === 'pending_approval' && ! $purchaseRequest->purchaseOrder()->exists(), 404);
        $purchaseRequest->load(['items.product.unit']);

        return view('purchasing.request-create', [
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'productOptions' => Product::where('is_active', true)->with('unit')->orderBy('name')->get()->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'locations' => Location::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'purchaseRequest' => $purchaseRequest,
            'initialItems' => session()->getOldInput('items', $purchaseRequest->items->map(fn ($item) => [
                'product_id' => $item->product_id, 'quantity' => $item->quantity,
                'estimated_unit_price' => $item->estimated_unit_price, 'notes' => $item->notes,
            ])->values()->all()),
        ]);
    }

    public function updateRequest(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        abort_unless($purchaseRequest->status === 'pending_approval' && ! $purchaseRequest->purchaseOrder()->exists(), 404);
        $data = $this->validatePurchaseRequest($request, $purchaseRequest);
        $pr = $service->updateRequest($purchaseRequest, $data);

        return redirect()->route('purchasing.requests.show', $pr)->with('status', 'Purchase Request diperbarui dan tetap menunggu persetujuan Management.');
    }

    private function validatePurchaseRequest(Request $request, ?PurchaseRequest $purchaseRequest = null): array
    {
        return $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('purchase_requests', 'reference')->ignore($purchaseRequest?->id)],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')->whereIn('type', [Location::TYPE_WAREHOUSE, Location::TYPE_OUTLET])->where('is_active', true)->whereNull('deleted_at')],
            'priority' => ['required', Rule::in(['normal', 'emergency'])],
            'justification' => ['required', 'string', 'min:5', 'max:5000'],
            'recommended_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'required_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'items.*.estimated_unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'justification.required' => 'Alasan pembelian wajib diisi.',
            'items.*.estimated_unit_price.required' => 'Lengkapi estimasi harga satuan setiap barang.',
        ]);
    }

    public function showRequest(PurchaseRequest $purchaseRequest): View
    {
        $user = auth()->user();
        $canReviewPurchaseRequest = $user->hasPermissionTo('purchase-request.approve');
        abort_unless($user->hasPermissionTo('purchasing.manage') || $canReviewPurchaseRequest, 403);
        return view('purchasing.request-show', [
            'purchaseRequest' => $purchaseRequest->load(['requester', 'reviewer', 'approver', 'rejector', 'location', 'recommendedSupplier', 'items.product.unit', 'purchaseOrder']),
            'canReviewPurchaseRequest' => $canReviewPurchaseRequest && (int) $purchaseRequest->requested_by !== (int) $user->id,
            'minimumStocks' => $purchaseRequest->location_id
                ? \App\Models\ProductLocationStockThreshold::where('location_id', $purchaseRequest->location_id)->pluck('minimum_quantity', 'product_id')
                : collect(),
        ]);
    }

    public function createOrder(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        abort_unless($purchaseRequest->status === 'approved' && ! $purchaseRequest->purchaseOrder()->where('status', '!=', 'cancelled')->exists(), 404);
        return view('purchasing.order-create', [
            'purchaseRequest' => $purchaseRequest->load('items.product.unit'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'locations' => $purchaseRequest->location_id
                ? Location::whereKey($purchaseRequest->location_id)->where('is_active', true)->get()
                : Location::where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'items' => $purchaseRequest->items->map(fn ($item) => ['product_id' => $item->product_id, 'description' => $item->notes, 'quantity' => $item->quantity, 'unit_price' => $item->estimated_unit_price ?? 0, 'discount_amount' => 0])->values(),
            'purchaseOrder' => null,
        ]);
    }

    public function createDirectOrder(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        return view('purchasing.order-create', [
            'purchaseRequest' => null, 'purchaseOrder' => null, 'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('type')->orderBy('name')->get(), 'items' => collect(),
        ]);
    }

    public function editDraftOrder(PurchaseOrder $purchaseOrder): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage') && $purchaseOrder->status === 'draft', 403);
        $purchaseOrder->load(['request', 'items']);
        return view('purchasing.order-create', [
            'purchaseRequest' => $purchaseOrder->request, 'purchaseOrder' => $purchaseOrder,
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(), 'locations' => Location::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'items' => $purchaseOrder->items->map(fn ($item) => ['product_id' => $item->product_id, 'description' => $item->description, 'quantity' => $item->qty_ordered ?: $item->quantity, 'unit_price' => $item->unit_price ?? 0, 'discount_amount' => $item->discount_amount ?? 0])->values(),
        ]);
    }

    public function storeOrder(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $this->validateOrder($request, $purchaseRequest->location_id);
        $order = $service->createOrder($purchaseRequest, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Draft Purchase Order berhasil dibuat. Tinjau lalu terbitkan PO.');
    }

    public function storeDirectOrder(Request $request, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $this->validateOrder($request);
        $order = $service->createOrder(null, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Draft Purchase Order berhasil dibuat. Tinjau lalu terbitkan PO.');
    }

    public function updateDraftOrder(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage') && $purchaseOrder->status === 'draft', 403);
        $data = $this->validateOrder($request, $purchaseOrder->request?->location_id);
        $order = $service->updateDraftOrder($purchaseOrder, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Draft PO berhasil diperbarui.');
    }

    private function validateOrder(Request $request, ?int $lockedLocationId = null): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'location_id' => $lockedLocationId ? ['required', 'integer', Rule::in([$lockedLocationId])] : ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'order_date' => ['required', 'date'], 'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'payment_terms' => ['required', Rule::in(['cod', 'transfer_before_delivery', 'net_7', 'net_14', 'net_30'])],
            'delivery_terms' => ['nullable', 'string', 'max:255'], 'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'shipping_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'], 'other_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:10000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.description' => ['nullable', 'string', 'max:1000'], 'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);
    }

    public function issueOrder(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('purchasing.manage'), 403);
        $order = $service->issueOrder($purchaseOrder);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Purchase Order diterbitkan dan siap dikirim ke supplier.');
    }

    public function cancelOrder(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('purchasing.manage'), 403);
        $service->cancelDraftOrder($purchaseOrder);
        return redirect()->route('purchasing.index')->with('status', 'Purchase Order dibatalkan.');
    }

    public function showOrder(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeView();
        $this->assertReceivingLocationScope($purchaseOrder);
        $purchaseOrder->load(['request', 'supplier', 'location', 'destinationLocation', 'creator', 'supplierFollowUpBy', 'items.product.unit', 'stockIn.items.product.unit', 'stockIn.evidences', 'invoices.items.product.unit']);
        $invoice = $purchaseOrder->invoices->sortByDesc('created_at')->first();
        $invoiceQuantities = $invoice?->items->keyBy('product_id')->map(fn ($line) => (float) $line->quantity) ?? collect();
        $receivedQuantities = $purchaseOrder->stockIn
            ->filter(fn ($receipt) => in_array($receipt->status, ['approved', 'pending_approval'], true))
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

    public function printOrder(PurchaseOrder $purchaseOrder): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $purchaseOrder->load(['request', 'supplier', 'destinationLocation', 'location', 'creator', 'items.product.unit']);
        return view('purchasing.order-print', ['order' => $purchaseOrder]);
    }

    public function receivingOrders(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('stock.manage'), 403);
        $user = $request->user();
        $isWarehouseStaff = $user->isWarehouseStaff();
        $isOutletStaff = $user->hasRole('staff_outlet');
        $assignedLocationIds = $isWarehouseStaff ? $user->assignedWarehouseLocationIds() : ($isOutletStaff ? $user->assignedOutletLocationIds() : []);
        if ($isWarehouseStaff || $isOutletStaff) abort_unless($assignedLocationIds, 403, 'Akun Staff belum memiliki lokasi aktif yang ditugaskan.');
        $query = PurchaseOrder::with(['supplier', 'location', 'items.product.unit', 'invoices.items'])
            ->whereIn('status', ['issued', 'open', 'partially_received'])
            ->when($isWarehouseStaff || $isOutletStaff, fn ($orders) => $orders->whereIn('location_id', $assignedLocationIds))
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
        $this->assertReceivingLocationScope($purchaseOrder);
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
