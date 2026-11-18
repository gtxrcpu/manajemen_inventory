<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roleIds = $data['role_ids'];
            unset($data['role_ids']);
            $data['whatsapp_phone'] = $this->normalizePhone($data['whatsapp_phone'] ?? null);

            $user = User::create($data + ['is_active' => true]);
            $user->roles()->sync($roleIds);
            $user->load('roles');

            $this->auditLogger->record('user.created', $user, [
                'after' => $this->snapshot($user),
            ]);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = $this->snapshot($user);
            $roleIds = $data['role_ids'];
            unset($data['role_ids']);
            $data['whatsapp_phone'] = $this->normalizePhone($data['whatsapp_phone'] ?? null);

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $newRoleIds = collect($roleIds)->map(fn ($id) => (int) $id)->sort()->values();
            $currentRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->sort()->values();

            if ($user->is(auth()->user()) && $newRoleIds->all() !== $currentRoleIds->all()) {
                throw ValidationException::withMessages([
                    'role_ids' => 'Role akun yang sedang digunakan tidak dapat diubah sendiri.',
                ]);
            }

            $willRemainAdmin = ($data['is_active'] ?? $user->is_active)
                && Role::where('name', 'super_admin')->whereIn('id', $newRoleIds)->exists();
            $this->ensureActiveAdministratorRemains($user, $willRemainAdmin);

            $user->fill($data)->save();
            $user->roles()->sync($newRoleIds->all());
            $user->load('roles');

            $this->auditLogger->record('user.updated', $user, [
                'before' => $before,
                'after' => $this->snapshot($user),
            ]);

            return $user;
        });
    }

    public function toggleActive(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->is(auth()->user()) && $user->is_active) {
                throw ValidationException::withMessages([
                    'user' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan sendiri.',
                ]);
            }

            $nextState = ! $user->is_active;
            if (! $nextState) {
                $this->ensureActiveAdministratorRemains($user, false);
            }

            $before = $this->snapshot($user);
            $user->forceFill(['is_active' => $nextState])->save();

            $this->auditLogger->record($nextState ? 'user.activated' : 'user.deactivated', $user, [
                'before' => $before,
                'after' => $this->snapshot($user),
            ]);
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->is(auth()->user())) {
                throw ValidationException::withMessages([
                    'user' => 'Akun yang sedang digunakan tidak dapat dihapus sendiri.',
                ]);
            }

            $this->ensureActiveAdministratorRemains($user, false);
            $before = $this->snapshot($user);
            $user->delete();

            $this->auditLogger->record('user.deleted', $user, ['before' => $before]);
        });
    }

    public function restore(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $user = User::withTrashed()->lockForUpdate()->findOrFail($userId);
            $before = ['deleted_at' => $user->deleted_at?->toIso8601String(), ...$this->snapshot($user)];
            $user->restore();

            $this->auditLogger->record('user.restored', $user, [
                'before' => $before,
                'after' => $this->snapshot($user),
            ]);
        });
    }

    private function ensureActiveAdministratorRemains(User $user, bool $willRemainAdmin): void
    {
        if (! $user->is_active || ! $user->hasRole('super_admin') || $willRemainAdmin) {
            return;
        }

        Role::where('name', 'super_admin')->lockForUpdate()->first();

        $activeAdmins = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->count();

        if ($activeAdmins <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Minimal satu akun Super Admin aktif harus dipertahankan.',
            ]);
        }
    }

    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'location_id' => $user->location_id,
            'whatsapp_phone' => $this->maskPhone($user->whatsapp_phone),
            'roles' => $user->roles()->pluck('name')->sort()->values()->all(),
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) return null;
        return preg_replace('/\D+/', '', $phone);
    }

    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) return null;
        return str_repeat('*', max(0, strlen($phone) - 4)).substr($phone, -4);
    }
}
