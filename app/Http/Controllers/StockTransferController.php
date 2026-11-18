<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferRequest;
use App\Services\StockTransferService;
use App\Services\BatchInventoryService;
use App\Services\TransferArrivalAlertService;
use App\Services\TransferReceiptAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    private function canManage($user): bool { return $user->hasPermissionTo('transfer.manage'); }
    private function isPic($user): bool { return $user->isOutletUser(); }

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $this->canManage($user);
        $canReceive = $user->hasPermissionTo('transfer.receive');
        $canResolveDiscrepancy = $user->hasPermissionTo('transfer.resolve_discrepancy');
        $isWarehousePic = $user->hasRole('pic_warehouse');
        $isWarehouseStaff = $user->isWarehouseStaff();
        $warehouseLocationIds = $isWarehouseStaff ? $user->assignedWarehouseLocationIds() : [];
        if ($isWarehouseStaff) abort_unless($warehouseLocationIds, 403, 'Akun Staff Gudang belum memiliki gudang aktif yang ditugaskan.');
        $canApprove = $user->hasPermissionTo('transfer.approve') && ($user->hasRole('super_admin') || $isWarehousePic);
        $canViewAll = $canManage || $canResolveDiscrepancy;
        abort_unless($canManage || $canReceive || $canResolveDiscrepancy || $canApprove, 403);
        $picLocationIds = $this->isPic($user) ? $user->assignedOutletLocationIds() : [];
        if ($this->isPic($user)) abort_unless($picLocationIds, 403, 'Akun PIC Outlet belum memiliki outlet yang ditugaskan.');
        if ($isWarehousePic) {
            abort_unless($user->location_id && Location::whereKey($user->location_id)->where('type', Location::TYPE_WAREHOUSE)->exists(), 403, 'Akun PIC Gudang belum memiliki gudang yang ditugaskan.');
        }

        $transfersQuery = StockTransfer::with(['sourceLocation', 'destinationLocation', 'dispatcher', 'preparedBy', 'approver', 'receiver', 'items']);
        $requestsQuery  = StockTransferRequest::with(['location', 'requester', 'items.product.unit']);
        if ($isWarehousePic) {
            $transfersQuery->where('source_location_id', $user->location_id);
            $requestsQuery->whereRaw('1 = 0');
        } elseif ($isWarehouseStaff) {
            $transfersQuery->whereIn('source_location_id', $warehouseLocationIds);
        } elseif (! $canManage && ! $canResolveDiscrepancy) {
            $transfersQuery->whereIn('destination_location_id', $picLocationIds);
            $requestsQuery->whereIn('location_id', $picLocationIds);
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
        if ($isWarehousePic) $baseQuery->where('source_location_id', $user->location_id);
        elseif ($isWarehouseStaff) $baseQuery->whereIn('source_location_id', $warehouseLocationIds);
        elseif (! $canManage && ! $canResolveDiscrepancy) $baseQuery->whereIn('destination_location_id', $picLocationIds);
        $hasResolutionTable = Schema::hasTable('stock_transfer_discrepancy_resolutions');
        $outstandingQuery = \App\Models\StockTransferItem::whereHas('transfer', fn ($q) => $q->whereIn('status', ['in_transit', 'partially_received', 'discrepancy_open'])->when($isWarehousePic, fn ($sub) => $sub->where('source_location_id', $user->location_id))->when($isWarehouseStaff, fn ($sub) => $sub->whereIn('source_location_id', $warehouseLocationIds))->when(! $canViewAll && ! $isWarehousePic, fn ($sub) => $sub->whereIn('destination_location_id', $picLocationIds)));
        $outstandingExpression = $hasResolutionTable
            ? 'COALESCE(SUM(quantity - received_quantity - COALESCE((SELECT SUM(resolutions.quantity) FROM stock_transfer_discrepancy_resolutions as resolutions WHERE resolutions.stock_transfer_item_id = stock_transfer_items.id), 0)), 0) as outstanding_quantity'
            : 'COALESCE(SUM(quantity - received_quantity), 0) as outstanding_quantity';
        $stats = [
            'total'           => $baseQuery->count(),
            'in_transit'      => (clone $baseQuery)->whereIn('status', ['in_transit', 'partially_received', 'discrepancy_open'])->count(),
            'in_transit_quantity' => (float) ($outstandingQuery->selectRaw($outstandingExpression)->first()?->outstanding_quantity ?? 0),
            'open_discrepancies' => (clone $baseQuery)->where('status', 'discrepancy_open')->count(),
            'received'        => (clone $baseQuery)->whereIn('status', ['received', 'received_with_discrepancy'])->count(),
            'pending_approval' => (clone $baseQuery)->where('status', 'pending_approval')->count(),
            'pending_requests'=> $requestsQuery->toBase()->count(),
        ];

        return view('inventory.transfers.index', [
            'transfers'        => $transfersQuery->orderByRaw("CASE WHEN status = 'pending_approval' THEN 0 ELSE 1 END")->latest('dispatched_at')->latest()->paginate(15, ['*'], 'transfers'),
            'requests'         => StockTransferRequest::with(['location', 'requester', 'items.product.unit'])
                ->when(! $canManage && $this->isPic($user), fn ($q) => $q->whereIn('location_id', $picLocationIds))
                ->when($isWarehousePic, fn ($q) => $q->whereRaw('1 = 0'))
                ->whereIn('status', ['pending', 'picking'])->latest('requested_at')->paginate(15, ['*'], 'requests'),
            'canManage'        => $canManage,
            'canReceive'       => $canReceive,
            'canApprove'       => $canApprove,
            'isWarehousePic'   => $isWarehousePic,
            'canResolveDiscrepancy' => $canResolveDiscrepancy,
            'isPic'            => $this->isPic($user),
            'stats'            => $stats,
        ]);
    }

    public function create(Request $request, BatchInventoryService $batchInventory): View
    {
        abort_unless($this->canManage($request->user()), 403);
        $warehouseLocationIds = $request->user()->isWarehouseStaff() ? $request->user()->assignedWarehouseLocationIds() : [];
        if ($request->user()->isWarehouseStaff()) abort_unless($warehouseLocationIds, 403, 'Akun Staff Gudang belum memiliki gudang aktif yang ditugaskan.');
        $warehouses = Location::where('type', Location::TYPE_WAREHOUSE)
            ->where('is_active', true)
            ->when($request->user()->isWarehouseStaff(), fn ($query) => $query->whereIn('id', $warehouseLocationIds))
            ->orderBy('name')->get();
        $sourceLocationIds = $warehouses->modelKeys();
        $transferRequest = null;
        if ($request->filled('request_id')) {
            $transferRequest = StockTransferRequest::with('items')->findOrFail($request->integer('request_id'));
            abort_unless($transferRequest->status === 'picking', 422);
        }
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        $stockOptions = $batchInventory->stockOutOptions($sourceLocationIds);
        $availableByProductLocation = [];
        foreach ($products as $product) {
            foreach ($sourceLocationIds as $locationId) {
                $eligibleBatches = collect($stockOptions[$product->id][$locationId] ?? [])
                    ->reject(fn (array $batch) => $batch['expired'] ?? false);
                $availableByProductLocation[$product->id][$locationId] = (float) $eligibleBatches->sum('quantity');
            }
        }
        $pendingTransfers = StockTransfer::with('items')
            ->whereIn('source_location_id', $sourceLocationIds)
            ->where('status', 'pending_approval')->get();
        foreach ($pendingTransfers as $pendingTransfer) {
            foreach ($pendingTransfer->items as $item) {
                $productId = (int) $item->product_id;
                $locationId = (int) $pendingTransfer->source_location_id;
                $availableByProductLocation[$productId][$locationId] = max(
                    0,
                    (float) ($availableByProductLocation[$productId][$locationId] ?? 0) - (float) $item->quantity,
                );
            }
        }

        return view('inventory.transfers.create', [
            'warehouses' => $warehouses,
            'isWarehouseStaff' => $request->user()->isWarehouseStaff(),
            'assignedWarehouse' => $request->user()->isWarehouseStaff() ? $warehouses->first() : null,
            'outlets' => Location::where('type', Location::TYPE_OUTLET)->where('is_active', true)->orderBy('name')->get(),
            'transferRequest' => $transferRequest,
            'requestItems' => $transferRequest?->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity])->values() ?? collect(),
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'stockAvailability' => $availableByProductLocation,
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);
        $data = $request->validate([
            'request_id' => ['nullable', 'integer', 'exists:stock_transfer_requests,id'],
            'source_location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'destination_location_id' => ['required', 'integer', 'different:source_location_id', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'notes' => ['nullable', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ], ['reference.unique' => 'Nomor referensi transfer sudah pernah digunakan.', 'items.required' => 'Tambahkan minimal satu barang untuk didistribusikan.']);
        if ($request->user()->isWarehouseStaff()) {
            $assignedWarehouseLocationIds = $request->user()->assignedWarehouseLocationIds();
            abort_unless($assignedWarehouseLocationIds, 403, 'Akun Staff Gudang belum memiliki gudang aktif yang ditugaskan.');
            abort_unless(in_array((int) $data['source_location_id'], $assignedWarehouseLocationIds, true), 403);
            $data['source_location_id'] = $assignedWarehouseLocationIds[0];
        }
        $transfer = $service->dispatch($data);
        return redirect()->route('inventory.transfers.show', $transfer)->with('status', 'Transfer dibuat dan menunggu persetujuan PIC Gudang. Stok gudang belum dikurangi.');
    }

    public function storeRequest(Request $request, StockTransferService $service): RedirectResponse
    {
        $user = $request->user();
        $locationIds = $this->isPic($user) ? $user->assignedOutletLocationIds() : [];
        abort_unless($this->isPic($user) && $locationIds, 403);
        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL,is_active,1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
        $data['location_id'] = $request->filled('location_id') ? $request->integer('location_id') : ($user->location_id ?: $locationIds[0]);
        abort_unless(in_array((int) $data['location_id'], $locationIds, true), 403);
        $transferRequest = $service->request($data);
        return redirect()->route('inventory.transfers.index')->with('status', 'Permintaan stok '.$transferRequest->reference.' berhasil dikirim ke gudang.');
    }

    public function createRequest(Request $request): View
    {
        $user = $request->user();
        $locationIds = $this->isPic($user) ? $user->assignedOutletLocationIds() : [];
        abort_unless($this->isPic($user) && $locationIds, 403);
        $products = Product::where('is_active', true)->with('unit')->orderBy('name')->get();
        return view('inventory.transfers.request-create', [
            'products' => $products,
            'productOptions' => $products->map(fn (Product $product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit' => $product->unit->symbol])->values(),
            'locations' => Location::whereIn('id', $locationIds)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedLocationId' => $user->location_id ?: $locationIds[0],
        ]);
    }

    public function show(Request $request, StockTransfer $stockTransfer): View
    {
        $user = $request->user();
        $resolutionSchemaReady = Schema::hasTable('stock_transfer_discrepancy_resolutions');
        $stockTransfer->load(['sourceLocation', 'destinationLocation', 'dispatcher', 'preparedBy', 'approver', 'receiver', 'stockOut', 'stockIn',
            'request', 'items.product.unit', 'items.sourceBatch', 'items.destinationBatch', 'receipts.receiver', 'receipts.stockIn', 'receipts.items']);
        if ($resolutionSchemaReady) {
            $stockTransfer->load('items.discrepancyResolutions.resolver');
        } else {
            $stockTransfer->items->each(fn ($item) => $item->setRelation('discrepancyResolutions', collect()));
        }
        $isAssignedWarehouseStaff = $user->isWarehouseStaff()
            && in_array((int) $stockTransfer->source_location_id, $user->assignedWarehouseLocationIds(), true);
        if ($user->isWarehouseStaff()) abort_unless($isAssignedWarehouseStaff, 403);
        $assignedDestination = $user->hasRole('super_admin') || ($user->isOutletUser() && $user->canAccessOutletLocation((int) $stockTransfer->destination_location_id));
        $assignedSource = $user->hasRole('super_admin') || ($user->hasRole('pic_warehouse') && (int) $user->location_id === (int) $stockTransfer->source_location_id);
        $canApprove = $user->hasPermissionTo('transfer.approve') && $assignedSource;
        $canResolveDiscrepancy = $resolutionSchemaReady && $user->hasPermissionTo('transfer.resolve_discrepancy');
        abort_unless(($this->canManage($user) && (! $user->isWarehouseStaff() || $isAssignedWarehouseStaff)) || ($user->hasPermissionTo('transfer.receive') && $assignedDestination) || $canResolveDiscrepancy || $canApprove, 403);
        $hasDiscrepancies = $stockTransfer->items->contains(fn ($item) => (float) $item->discrepancy_quantity > 0 || $item->discrepancyResolutions->isNotEmpty());
        return view('inventory.transfers.show', [
            'transfer' => $stockTransfer,
            'canReceive' => $user->hasPermissionTo('transfer.receive') && $assignedDestination,
            'canApprove' => $canApprove,
            'canResolveDiscrepancy' => $canResolveDiscrepancy,
            'hasDiscrepancies' => $hasDiscrepancies,
            'resolutionSchemaReady' => $resolutionSchemaReady,
        ]);
    }

    public function approve(Request $request, StockTransfer $stockTransfer, StockTransferService $service, TransferArrivalAlertService $arrivalAlerts): RedirectResponse
    {
        $user = $request->user();
        $assignedSource = $user->hasRole('super_admin') || ($user->hasRole('pic_warehouse') && (int) $user->location_id === (int) $stockTransfer->source_location_id);
        abort_unless($user->hasPermissionTo('transfer.approve') && $assignedSource, 403);

        $expectedArrivalAt = $this->validatedExpectedArrival($request, 'Isi estimasi waktu tiba sebelum menyetujui pengiriman.');
        $transfer = $service->approveDispatch($stockTransfer, $expectedArrivalAt);
        $arrivalAlerts->queue($transfer);

        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('status', 'Transfer disetujui. Stok gudang dikurangi dan barang berstatus dalam perjalanan.');
    }

    public function updateEta(Request $request, StockTransfer $stockTransfer, TransferArrivalAlertService $arrivalAlerts): RedirectResponse
    {
        $user = $request->user();
        $assignedSource = $user->hasRole('super_admin') || ($user->hasRole('pic_warehouse') && (int) $user->location_id === (int) $stockTransfer->source_location_id);
        abort_unless($user->hasPermissionTo('transfer.approve') && $assignedSource, 403);
        abort_unless($stockTransfer->status === 'in_transit', 409, 'Estimasi hanya dapat diisi untuk transfer dalam perjalanan.');

        $expectedArrivalAt = $this->validatedExpectedArrival($request, 'Isi estimasi waktu tiba sebelum memberi tahu outlet.');
        $stockTransfer->update(['expected_arrival_at' => $expectedArrivalAt]);
        $queued = $arrivalAlerts->queue($stockTransfer);

        return back()->with('status', $queued
            ? 'Estimasi disimpan. Notifikasi WhatsApp sudah masuk antrean untuk Staff Outlet tujuan.'
            : 'Estimasi disimpan. WhatsApp ETA belum aktif atau belum ada Staff Outlet tujuan dengan nomor aktif.');
    }

    private function validatedExpectedArrival(Request $request, string $requiredMessage): Carbon
    {
        $data = $request->validate([
            'expected_arrival_at' => [
                'required',
                'date_format:Y-m-d\\TH:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        $eta = Carbon::createFromFormat('Y-m-d\\TH:i', (string) $value, 'Asia/Jakarta');
                    } catch (\Throwable) {
                        $fail('Masukkan tanggal dan waktu estimasi yang valid.');
                        return;
                    }
                    if (! $eta) {
                        $fail('Masukkan tanggal dan waktu estimasi yang valid.');
                        return;
                    }

                    if ($eta->lessThanOrEqualTo(now('Asia/Jakarta'))) {
                        $fail('Estimasi waktu tiba harus setelah waktu sekarang (WIB).');
                    }
                },
            ],
        ], [
            'expected_arrival_at.required' => $requiredMessage,
            'expected_arrival_at.date_format' => 'Gunakan format tanggal dan waktu estimasi yang valid.',
        ]);

        return Carbon::createFromFormat('Y-m-d\\TH:i', $data['expected_arrival_at'], 'Asia/Jakarta')->utc();
    }

    public function receive(Request $request, StockTransfer $stockTransfer, StockTransferService $service, TransferReceiptAlertService $receiptAlerts): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermissionTo('transfer.receive') && ($user->hasRole('super_admin') || ($user->isOutletUser() && $user->canAccessOutletLocation((int) $stockTransfer->destination_location_id))), 403);
        $data = $request->validate([
            'items' => ['required', 'array'], 'items.*' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'is_final' => ['nullable', 'boolean'], 'discrepancy_reason' => ['nullable', 'string', 'max:5000'], 'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $result = $service->receive($stockTransfer, $data);
        $queued = $receiptAlerts->queue($result['receipt']);
        return redirect()->route('inventory.transfers.show', $result['transfer'])->with('status', $queued
            ? 'Penerimaan berhasil dicatat. Stok outlet diperbarui dan Staff Gudang diberi tahu melalui WhatsApp.'
            : 'Penerimaan berhasil dicatat dan stok outlet diperbarui. Notifikasi Staff Gudang belum aktif atau belum ada nomor WhatsApp yang terdaftar.');
    }

    public function resolveDiscrepancy(Request $request, StockTransfer $stockTransfer, int $transferItem, StockTransferService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('transfer.resolve_discrepancy'), 403);
        abort_unless(Schema::hasTable('stock_transfer_discrepancy_resolutions'), 503, 'Penyelesaian discrepancy belum aktif. Migration fitur ini perlu dipasang terlebih dahulu.');
        $data = $request->validate([
            'resolution_type' => ['required', Rule::in(['lost', 'damaged', 'other'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'reason' => ['required', 'string', 'min:5', 'max:5000'],
        ]);
        $transfer = $service->resolveDiscrepancy($stockTransfer, $transferItem, $data);
        return redirect()->route('inventory.transfers.show', $transfer)->with('status', 'Penyelesaian discrepancy tercatat dengan audit.');
    }

    public function storePick(StockTransferRequest $stockTransferRequest, StockTransferService $service): RedirectResponse
    {
        abort_unless($this->canManage(request()->user()), 403);
        $service->markPicking($stockTransferRequest);
        return redirect()->route('inventory.transfers.create', ['request_id' => $stockTransferRequest->id])->with('status', 'Permintaan masuk tahap picking. Sesuaikan jumlah sebelum mengirim.');
    }
}
