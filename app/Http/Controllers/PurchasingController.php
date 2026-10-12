<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Services\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchasingController extends Controller
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage') || auth()->user()->hasPermissionTo('purchasing.approve') || auth()->user()->hasPermissionTo('stock.manage'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $prQuery = PurchaseRequest::with(['requester', 'items']);
        $poQuery = PurchaseOrder::with(['request', 'supplier', 'location', 'items']);

        // Search & filter
        if ($q = $request->input('q')) {
            $prQuery->where('reference', 'like', "%{$q}%");
            $poQuery->where('reference', 'like', "%{$q}%");
        }
        if ($prStatus = $request->input('pr_status')) {
            $prQuery->where('status', $prStatus);
        }
        if ($poStatus = $request->input('po_status')) {
            $poQuery->where('status', $poStatus);
        }

        // Stats
        $stats = [
            'pr_total'    => PurchaseRequest::count(),
            'pr_pending'  => PurchaseRequest::where('status', 'pending_approval')->count(),
            'pr_approved' => PurchaseRequest::where('status', 'approved')->count(),
            'po_total'    => PurchaseOrder::count(),
            'po_pending'  => PurchaseOrder::whereIn('status', ['ordered', 'issued'])->count(),
            'po_received' => PurchaseOrder::where('status', 'received')->count(),
        ];

        return view('purchasing.index', [
            'requests' => $prQuery->latest()->paginate(10, ['*'], 'requests_page'),
            'orders'   => $poQuery->latest()->paginate(10, ['*'], 'orders_page'),
            'stats'    => $stats,
        ]);
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
        return redirect()->route('purchasing.requests.show', $pr)->with('status', 'Purchase Request dikirim untuk persetujuan.');
    }

    public function showRequest(PurchaseRequest $purchaseRequest): View
    {
        $this->authorizeView();
        return view('purchasing.request-show', ['purchaseRequest' => $purchaseRequest->load(['requester', 'reviewer', 'items.product.unit', 'purchaseOrder'])]);
    }

    public function reviewRequest(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.approve'), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000']]);
        $service->reviewRequest($purchaseRequest, $data['decision'], $data['review_note'] ?? null);
        return back()->with('status', $data['decision'] === 'approved' ? 'Purchase Request disetujui.' : 'Purchase Request ditolak.');
    }

    public function createOrder(PurchaseRequest $purchaseRequest): View
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        abort_unless($purchaseRequest->status === 'approved' && ! $purchaseRequest->purchaseOrder()->exists(), 404);
        return view('purchasing.order-create', [
            'purchaseRequest' => $purchaseRequest->load('items.product.unit'),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeOrder(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('purchasing.manage'), 403);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('purchase_orders', 'reference')],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id,deleted_at,NULL,is_active,1'],
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'expected_delivery' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = $service->createOrder($purchaseRequest, $data);
        return redirect()->route('purchasing.orders.show', $order)->with('status', 'Purchase Order berhasil diterbitkan.');
    }

    public function showOrder(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeView();
        return view('purchasing.order-show', ['order' => $purchaseOrder->load(['request', 'supplier', 'location', 'creator', 'items.product.unit', 'stockIn'])]);
    }

    public function receiveOrder(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermissionTo('stock.manage'), 403);
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_at'],
        ]);
        $receipt = $service->receiveOrder($purchaseOrder, $data['received_at'], $data['items'] ?? []);
        return redirect()->route('inventory.stock-in.show', $receipt)->with('status', 'Penerimaan penuh dicatat sebagai Stock In.');
    }
}
