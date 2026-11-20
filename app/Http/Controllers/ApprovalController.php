<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\PurchaseRequest;
use App\Services\PurchasingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReviewStockIns = $user->hasPermissionTo('stock-in.approve');
        $canReviewTransfers = $user->hasPermissionTo('transfer.approve');
        $canReviewOpnames = $user->hasPermissionTo('opname.approve');
        $canReviewPurchaseRequests = $user->hasPermissionTo('purchase-request.approve');

        abort_unless($canReviewStockIns || $canReviewTransfers || $canReviewOpnames || $canReviewPurchaseRequests, 403);

        $stockInLocationIds = $user->hasRole('pic_outlet') ? $user->assignedOutletLocationIds() : [];
        $transferLocationIds = $user->hasRole('pic_warehouse') ? $user->assignedWarehouseLocationIds() : [];
        if ($user->hasRole('pic_outlet')) {
            abort_unless($stockInLocationIds, 403, 'Akun PIC Outlet belum memiliki outlet yang ditugaskan.');
        }
        if ($user->hasRole('pic_warehouse')) {
            abort_unless($transferLocationIds, 403, 'Akun PIC Gudang belum memiliki gudang yang ditugaskan.');
        }

        $stockInScope = fn ($query) => $query->when($user->hasRole('pic_outlet'), fn ($scoped) => $scoped->whereIn('location_id', $stockInLocationIds));
        $transferScope = fn ($query) => $query->when($user->hasRole('pic_warehouse'), fn ($scoped) => $scoped->whereIn('source_location_id', $transferLocationIds));
        $opnameLocationIds = $user->hasRole('pic_warehouse') ? $user->assignedWarehouseLocationIds() : $stockInLocationIds;
        if ($user->hasRole('pic_warehouse') || $user->hasRole('pic_outlet')) {
            abort_unless($opnameLocationIds, 403, 'PIC belum memiliki lokasi aktif yang ditugaskan.');
        }
        $opnameScope = fn ($query) => $query->when(
            $user->hasRole('pic_warehouse') || $user->hasRole('pic_outlet'),
            fn ($scoped) => $scoped->whereIn('location_id', $opnameLocationIds),
        );

        $pendingStockIns = $canReviewStockIns
            ? $stockInScope(StockIn::with(['location', 'receiver', 'items.product.unit'])->where('status', 'pending_approval'))->latest('received_at')->get()
            : collect();
        $pendingTransfers = $canReviewTransfers
            ? $transferScope(StockTransfer::with(['sourceLocation', 'destinationLocation', 'preparedBy', 'items'])->where('status', 'pending_approval'))->latest()->get()
            : collect();
        $pendingOpnames = $canReviewOpnames
            ? $opnameScope(StockOpname::with(['location', 'counter', 'items'])->where('status', 'pending_approval'))->latest()->get()
            : collect();
        $pendingPurchaseRequests = $canReviewPurchaseRequests
            ? PurchaseRequest::with(['requester', 'location', 'items.product.unit'])
                ->where('status', 'pending_approval')
                ->orderByRaw("CASE WHEN priority IN ('emergency', 'urgent') THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->get()
            : collect();

        $resolvedStockIns = $canReviewStockIns
            ? $stockInScope(StockIn::with(['location', 'receiver', 'decisionMaker'])->whereIn('status', ['approved', 'rejected'])->whereNotNull('decided_at'))->latest('decided_at')->limit(20)->get()
            : collect();
        $resolvedTransfers = $canReviewTransfers
            ? $transferScope(StockTransfer::with(['sourceLocation', 'destinationLocation', 'preparedBy', 'approver'])->whereNotNull('approved_at'))->latest('approved_at')->limit(20)->get()
            : collect();
        $resolvedOpnames = $canReviewOpnames
            ? $opnameScope(StockOpname::with(['location', 'counter', 'reviewer'])->whereIn('status', ['approved', 'rejected'])->whereNotNull('reviewed_at'))->latest('reviewed_at')->limit(20)->get()
            : collect();

        $resolvedDecisions = collect();
        foreach ($resolvedStockIns as $receipt) {
            $resolvedDecisions->push([
                'type' => 'Stock In', 'reference' => $receipt->reference, 'status' => $receipt->status,
                'location' => $receipt->location?->name ?? 'Lokasi tidak tersedia',
                'context' => 'Diajukan oleh '.($receipt->receiver?->name ?? 'Pengguna'),
                'reviewer' => $receipt->decisionMaker?->name ?? 'Pengguna', 'date' => $receipt->decided_at,
                'url' => route('inventory.stock-in.show', $receipt),
            ]);
        }
        foreach ($resolvedTransfers as $transfer) {
            $resolvedDecisions->push([
                'type' => 'Distribusi', 'reference' => $transfer->reference, 'status' => 'approved',
                'location' => ($transfer->sourceLocation?->name ?? 'Gudang'). ' → '.($transfer->destinationLocation?->name ?? 'Outlet'),
                'context' => 'Disiapkan oleh '.($transfer->preparedBy?->name ?? 'Pengguna'),
                'reviewer' => $transfer->approver?->name ?? 'Pengguna', 'date' => $transfer->approved_at,
                'url' => route('inventory.transfers.show', $transfer),
            ]);
        }
        foreach ($resolvedOpnames as $opname) {
            $resolvedDecisions->push([
                'type' => 'Stock Opname', 'reference' => $opname->reference, 'status' => $opname->status,
                'location' => $opname->location?->name ?? 'Lokasi tidak tersedia',
                'context' => 'Dihitung oleh '.($opname->counter?->name ?? 'Pengguna'),
                'reviewer' => $opname->reviewer?->name ?? 'Pengguna', 'date' => $opname->reviewed_at,
                'url' => route('inventory.stock-opname.show', $opname),
            ]);
        }
        $resolvedPurchaseRequests = $canReviewPurchaseRequests
            ? PurchaseRequest::with(['requester', 'reviewer'])->whereIn('status', ['approved', 'rejected'])
                ->whereNotNull('reviewed_by')->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(20)->get()
            : collect();
        foreach ($resolvedPurchaseRequests as $purchaseRequest) {
            $resolvedDecisions->push([
                'type' => 'Purchase Request', 'reference' => $purchaseRequest->reference, 'status' => $purchaseRequest->status,
                'location' => 'Permintaan pembelian',
                'context' => 'Diajukan oleh '.($purchaseRequest->requester?->name ?? 'Pengguna'),
                'reviewer' => $purchaseRequest->reviewer?->name ?? 'Pengguna', 'date' => $purchaseRequest->reviewed_at,
                'url' => route('purchasing.requests.show', $purchaseRequest),
            ]);
        }
        $resolvedDecisions = $resolvedDecisions->sortByDesc(fn (array $decision) => $decision['date']?->getTimestamp() ?? 0)->take(20)->values();

        return view('approvals.index', [
            'canReviewStockIns' => $canReviewStockIns,
            'canReviewTransfers' => $canReviewTransfers,
            'canReviewOpnames' => $canReviewOpnames,
            'canReviewPurchaseRequests' => $canReviewPurchaseRequests,
            'pendingStockIns' => $pendingStockIns,
            'pendingTransfers' => $pendingTransfers,
            'pendingOpnames' => $pendingOpnames,
            'pendingPurchaseRequests' => $pendingPurchaseRequests,
            'resolvedDecisions' => $resolvedDecisions,
            'stats' => [
                'stock_in_pending' => $pendingStockIns->count(),
                'transfer_pending' => $pendingTransfers->count(),
                'opname_pending' => $pendingOpnames->count(),
                'purchase_request_pending' => $pendingPurchaseRequests->count(),
            ],
        ]);
    }

    public function approvePurchaseRequest(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('purchase-request.approve'), 403);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:5000']]);
        $service->reviewRequest($purchaseRequest, 'approved', $data['review_note'] ?? null);

        return redirect()->route('purchasing.requests.show', $purchaseRequest)->with('status', 'Purchase Request disetujui. Purchasing sekarang dapat menerbitkan PO.');
    }

    public function rejectPurchaseRequest(Request $request, PurchaseRequest $purchaseRequest, PurchasingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('purchase-request.approve'), 403);
        $data = $request->validate(['review_note' => ['required', 'string', 'min:5', 'max:5000']]);
        $service->reviewRequest($purchaseRequest, 'rejected', $data['review_note']);

        return redirect()->route('purchasing.requests.show', $purchaseRequest)->with('status', 'Purchase Request ditolak. Alasan keputusan tersimpan.');
    }
}
