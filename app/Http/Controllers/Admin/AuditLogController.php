<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdministrationAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'entity' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = AdministrationAuditLog::query()
            ->with(['actor' => fn ($users) => $users->withTrashed()])
            ->when($request->filled('q'), function ($logs) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $logs->where(function ($search) use ($term) {
                    $search->where('action', 'like', $term)
                        ->orWhere('subject_type', 'like', $term)
                        ->orWhere('subject_id', 'like', $term)
                        ->orWhere('changes', 'like', $term)
                        ->orWhereHas('actor', fn ($actor) => $actor->withTrashed()->where('name', 'like', $term));
                });
            })
            ->when($request->filled('action'), fn ($logs) => $logs->where('action', $filters['action']))
            ->when($request->filled('actor_id'), fn ($logs) => $logs->where('actor_id', $filters['actor_id']))
            ->when($request->filled('entity'), fn ($logs) => $logs->where('subject_type', $filters['entity']))
            ->when($request->filled('date_from'), fn ($logs) => $logs->whereDate('created_at', '>=', $filters['date_from']))
            ->when($request->filled('date_to'), fn ($logs) => $logs->whereDate('created_at', '<=', $filters['date_to']));

        return view('admin.audit.index', [
            'logs' => $query->latest('id')->paginate(20)->withQueryString(),
            'actions' => AdministrationAuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'entities' => AdministrationAuditLog::query()->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'actors' => User::withTrashed()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }
}
