<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReviewStockIns = $user->hasPermissionTo('stock-in.approve');
        $canReviewTransfers = $user->hasPermissionTo('transfer.approve');
        $canReviewOpnames = $user->hasPermissionTo('opname.approve');

        abort_unless($canReviewStockIns || $canReviewTransfers || $canReviewOpnames, 403);

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
        $opnameScope = fn ($query) => $query->when($user->hasRole('pic_outlet'), fn ($scoped) => $scoped->whereIn('location_id', $stockInLocationIds));

        $pendingStockIns = $canReviewStockIns
            ? $stockInScope(StockIn::with(['location', 'receiver', 'items.product.unit'])->where('status', 'pending_approval'))->latest('received_at')->get()
            : collect();
        $pendingTransfers = $canReviewTransfers
            ? $transferScope(StockTransfer::with(['sourceLocation', 'destinationLocation', 'preparedBy', 'items'])->where('status', 'pending_approval'))->latest()->get()
            : collect();
        $pendingOpnames = $canReviewOpnames
            ? $opnameScope(StockOpname::with(['location', 'counter', 'items'])->where('status', 'pending_approval'))->latest()->get()
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
        $resolvedDecisions = $resolvedDecisions->sortByDesc(fn (array $decision) => $decision['date']?->getTimestamp() ?? 0)->take(20)->values();

        return view('approvals.index', [
            'canReviewStockIns' => $canReviewStockIns,
            'canReviewTransfers' => $canReviewTransfers,
            'canReviewOpnames' => $canReviewOpnames,
            'pendingStockIns' => $pendingStockIns,
            'pendingTransfers' => $pendingTransfers,
            'pendingOpnames' => $pendingOpnames,
            'resolvedDecisions' => $resolvedDecisions,
            'stats' => [
                'stock_in_pending' => $pendingStockIns->count(),
                'transfer_pending' => $pendingTransfers->count(),
                'opname_pending' => $pendingOpnames->count(),
            ],
        ]);
    }
}
