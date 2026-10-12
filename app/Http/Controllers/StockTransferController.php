<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferRequest;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    private function canManage($user): bool { return $user->hasPermissionTo('transfer.manage'); }
    private function isPic($user): bool { return $user->hasRole('pic_outlet'); }

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $this->canManage($user);
        $canReceive = $user->hasPermissionTo('transfer.receive');
        abort_unless($canManage || $canReceive, 403);
        if ($this->isPic($user)) abort_unless($user->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');

        $transfersQuery = StockTransfer::with(['sourceLocation', 'destinationLocation', 'dispatcher', 'receiver', 'items']);
        $requestsQuery  = StockTransferRequest::with(['location', 'requester', 'items.product.unit']);
        if (! $canManage) {
            $transfersQuery->where('destination_location_id', $user->location_id);
            $requestsQuery->where('location_id', $user->location_id);
        }

        // Search & filter
        if ($q = $request->input('q')) {
            $transfersQuery->where('reference', 'like', "%{$q}%");
        }
        if ($status = $request->input('status')) {
            $transfersQuery->where('status', $status);
        }
        if ($dateFrom = $request->input('date_from')) {
            $transfersQuery->whereDate('dispatched_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $transfersQuery->whereDate('dispatched_at', '<=', $dateTo);
        }

        // Stats
        $baseQuery = StockTransfer::query();
        if (! $canManage) $baseQuery->where('destination_location_id', $user->location_id);
        $stats = [
            'total'           => $baseQuery->count(),
            'in_transit'      => (clone $baseQuery)->where('status', 'in_transit')->count(),
            'received'        => (clone $baseQuery)->whereIn('status', ['received', 'received_with_discrepancy'])->count(),
            'pending_requests'=> $requestsQuery->toBase()->count(),
        ];

        return view('inventory.transfers.index', [
            'transfers'        => $transfersQuery->latest('dispatched_at')->latest()->paginate(15, ['*'], 'transfers'),
            'requests'         => StockTransferRequest::with(['location', 'requester', 'items.product.unit'])
                ->when(! $canManage, fn ($q) => $q->where('location_id', $user->location_id))
                ->whereIn('status', ['pending', 'picking'])->latest('requested_at')->paginate(15, ['*'], 'requests'),
            'canManage'        => $canManage,
            'canReceive'       => $canReceive,
            'isPic'            => $this->isPic($user),
            'stats'            => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManage($request->user()), 403);
        $transferRequest = null;
        if ($request->filled('request_id')) {
            $transferRequest = StockTransferRequest::with('items')->findOrFail($request->integer('request_id'));
            abort_unless($transferRequest->status === 'picking', 422);
        }
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        return view('inventory.transfers.create', [
            'warehouses' => Location::where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->orderBy('name')->get(),
            'outlets' => Location::where('type', Location::TYPE_OUTLET)->where('is_active', true)->orderBy('name')->get(),
            'transferRequest' => $transferRequest,
            'requestItems' => $transferRequest?->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->values() ?? collect(),
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100', Rule::unique('stock_transfers', 'reference')],
            'request_id' => ['nullable', 'integer', 'exists:stock_transfer_requests,id'],
            'source_location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'destination_location_id' => ['required', 'integer', 'different:source_location_id', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'notes' => ['nullable', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ], ['reference.unique' => 'Nomor referensi transfer sudah pernah digunakan.', 'items.required' => 'Tambahkan minimal satu barang untuk didistribusikan.']);
        $transfer = $service->dispatch($data);
        return redirect()->route('inventory.transfers.show', $transfer)->with('status', 'Barang berhasil dikirim. Stok outlet bertambah setelah penerimaan dicatat.');
    }

    public function storeRequest(Request $request, StockTransferService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->isPic($user) && $user->location_id, 403);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
        $data['location_id'] = $user->location_id;
        $transferRequest = $service->request($data);
        return redirect()->route('inventory.transfers.index')->with('status', 'Permintaan stok '.$transferRequest->reference.' berhasil dikirim ke gudang.');
    }

    public function createRequest(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->isPic($user) && $user->location_id, 403);
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        return view('inventory.transfers.request-create', [
            'products' => $products,
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
        ]);
    }

    public function show(Request $request, StockTransfer $stockTransfer): View
    {
        $user = $request->user();
        $stockTransfer->load(['sourceLocation', 'destinationLocation', 'dispatcher', 'receiver', 'stockOut', 'stockIn',
            'request', 'items.product.unit', 'items.sourceBatch', 'items.destinationBatch', 'receipts.receiver', 'receipts.stockIn', 'receipts.items']);
        $assignedDestination = $user->hasRole('super_admin') || $stockTransfer->destination_location_id === $user->location_id;
        abort_unless($this->canManage($user) || ($user->hasPermissionTo('transfer.receive') && $assignedDestination), 403);
        return view('inventory.transfers.show', ['transfer' => $stockTransfer, 'canReceive' => $user->hasPermissionTo('transfer.receive') && $assignedDestination]);
    }

    public function receive(Request $request, StockTransfer $stockTransfer, StockTransferService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermissionTo('transfer.receive') && ($user->hasRole('super_admin') || $stockTransfer->destination_location_id === $user->location_id), 403);
        $data = $request->validate([
            'items' => ['required', 'array'], 'items.*' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'is_final' => ['nullable', 'boolean'], 'discrepancy_reason' => ['nullable', 'string', 'max:5000'], 'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $transfer = $service->receive($stockTransfer, $data);
        return redirect()->route('inventory.transfers.show', $transfer)->with('status', 'Penerimaan transfer berhasil dicatat. Saldo outlet sudah diperbarui sesuai jumlah yang diterima.');
    }

    public function storePick(StockTransferRequest $stockTransferRequest, StockTransferService $service): RedirectResponse
    {
        abort_unless($this->canManage(request()->user()), 403);
        $service->markPicking($stockTransferRequest);
        return redirect()->route('inventory.transfers.create', ['request_id' => $stockTransferRequest->id])->with('status', 'Permintaan masuk tahap picking. Sesuaikan jumlah sebelum mengirim.');
    }
}
