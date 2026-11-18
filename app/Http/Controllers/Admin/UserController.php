<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AdministrationAuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Location;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        $users = User::withTrashed()
            ->with(['roles', 'location'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($nested) => $nested
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($status === 'active', fn ($query) => $query->whereNull('deleted_at')->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->whereNull('deleted_at')->where('is_active', false))
            ->when($status === 'deleted', fn ($query) => $query->whereNotNull('deleted_at'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $auditLogs = AdministrationAuditLog::with('actor')
            ->whereIn('subject_type', [User::class, Role::class])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.users.index', compact('users', 'status', 'auditLogs'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('label')->get();
        $locations = Location::whereIn('type', [Location::TYPE_OUTLET, Location::TYPE_WAREHOUSE])->where('is_active', true)->orderBy('type')->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'locations'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::orderBy('label')->get();
        $locations = Location::whereIn('type', [Location::TYPE_OUTLET, Location::TYPE_WAREHOUSE])->where('is_active', true)->orderBy('type')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'locations'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Perubahan pengguna berhasil disimpan.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->users->toggleActive($user);

        return back()->with('status', 'Status pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->users->delete($user);

        return redirect()->route('admin.users.index')->with('status', 'Pengguna dipindahkan ke daftar terhapus.');
    }

    public function restore(int $user): RedirectResponse
    {
        $this->users->restore($user);

        return redirect()->route('admin.users.index', ['status' => 'all'])
            ->with('status', 'Pengguna berhasil dipulihkan.');
    }
}
