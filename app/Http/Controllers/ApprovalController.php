<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\StockOpname;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canReviewRequests = $user->hasPermissionTo('purchasing.approve');
        $canReviewOpnames = $user->hasPermissionTo('opname.approve');
        abort_unless($canReviewRequests || $canReviewOpnames, 403);

        return view('approvals.index', [
            'canReviewRequests' => $canReviewRequests,
            'canReviewOpnames'  => $canReviewOpnames,
            'pendingRequests'   => $canReviewRequests ? PurchaseRequest::with(['requester', 'items'])->where('status', 'pending_approval')->latest()->get() : collect(),
            'pendingOpnames'    => $canReviewOpnames ? StockOpname::with(['location', 'counter', 'items'])->where('status', 'pending_approval')->latest()->get() : collect(),
            'resolvedRequests'  => $canReviewRequests ? PurchaseRequest::with(['requester', 'reviewer'])->whereIn('status', ['approved', 'rejected'])->latest('reviewed_at')->limit(10)->get() : collect(),
            'resolvedOpnames'   => $canReviewOpnames ? StockOpname::with(['location', 'counter', 'reviewer'])->whereIn('status', ['approved', 'rejected'])->latest('reviewed_at')->limit(10)->get() : collect(),
            'stats' => [
                'pr_pending'     => $canReviewRequests ? PurchaseRequest::where('status', 'pending_approval')->count() : 0,
                'opname_pending' => $canReviewOpnames  ? StockOpname::where('status', 'pending_approval')->count()    : 0,
                'pr_approved'    => $canReviewRequests ? PurchaseRequest::where('status', 'approved')->count()         : 0,
                'opname_approved'=> $canReviewOpnames  ? StockOpname::where('status', 'approved')->count()             : 0,
            ],
        ]);
    }
}
