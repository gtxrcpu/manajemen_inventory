<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\AdministrationAuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('label')->get();
        $permissions = Permission::where('name', '!=', 'users.manage')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role) {
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            $before = $role->permissions()->pluck('name')->sort()->values()->all();
            $editablePermissionIds = Permission::where('name', '!=', 'users.manage')->pluck('id');
            $requestedIds = collect($request->validated('permissions', []))
                ->map(fn ($id) => (int) $id)
                ->intersect($editablePermissionIds)
                ->values();

            if ($role->name === 'super_admin') {
                $requestedIds->push(Permission::where('name', 'users.manage')->value('id'));
            }

            $role->permissions()->sync($requestedIds->filter()->unique()->all());
            $after = $role->permissions()->pluck('name')->sort()->values()->all();

            AdministrationAuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'role.permissions_updated',
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'changes' => ['before' => $before, 'after' => $after],
            ]);
        });

        return back()->with('status', 'Permission role berhasil diperbarui.');
    }
}
