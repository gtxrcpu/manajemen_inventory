<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'location_id',
        'whatsapp_phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    /** Outlet locations assigned directly to a PIC or outlet staff member, plus temporary PIC substitutions. */
    public function assignedOutletLocationIds(): array
    {
        if ($this->isWarehouseStaff()) return [];
        if ($this->hasRole('staff_outlet')) {
            return $this->location_id && Location::query()->whereKey($this->location_id)
                ->where('type', Location::TYPE_OUTLET)->where('is_active', true)->exists()
                ? [(int) $this->location_id]
                : [];
        }
        if (! $this->hasRole('pic_outlet')) {
            return [];
        }

        $locationIds = $this->location_id && Location::query()->whereKey($this->location_id)
            ->where('type', Location::TYPE_OUTLET)->where('is_active', true)->exists()
            ? [(int) $this->location_id]
            : [];
        if (Schema::hasTable('pic_substitute_assignments')) {
            $locationIds = array_merge($locationIds, DB::table('pic_substitute_assignments')
                ->where('substitute_pic_user_id', $this->id)
                ->whereNull('revoked_at')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>', now())
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('users as primary_pics')
                    ->whereColumn('primary_pics.id', 'pic_substitute_assignments.primary_pic_user_id')
                    ->whereColumn('primary_pics.location_id', 'pic_substitute_assignments.location_id')
                    ->where('primary_pics.is_active', true)->whereNull('primary_pics.deleted_at'))
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('locations as assigned_locations')
                    ->whereColumn('assigned_locations.id', 'pic_substitute_assignments.location_id')
                    ->where('assigned_locations.is_active', true)->whereNull('assigned_locations.deleted_at'))
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('role_user as primary_role_user')
                    ->join('roles as primary_roles', 'primary_roles.id', '=', 'primary_role_user.role_id')
                    ->whereColumn('primary_role_user.user_id', 'pic_substitute_assignments.primary_pic_user_id')
                    ->where('primary_roles.name', 'pic_outlet'))
                ->pluck('location_id')->map(fn ($id) => (int) $id)->all());
        }

        return array_values(array_unique($locationIds));
    }

    public function isOutletUser(): bool
    {
        return ! $this->isWarehouseStaff() && ($this->hasRole('pic_outlet') || $this->hasRole('staff_outlet'));
    }

    public function isWarehouseStaff(): bool
    {
        return $this->hasRole('staff_gudang');
    }

    public function isWarehousePic(): bool
    {
        return $this->hasRole('pic_warehouse');
    }

    public function isLocationScopedUser(): bool
    {
        // Operational staff and PIC accounts are restricted to their assigned
        // location. Non-operational roles retain the global location selector.
        return ! $this->hasRole('super_admin') && ($this->isWarehouseStaff() || $this->isWarehousePic() || $this->isOutletUser());
    }

    /** @return array<int, int> */
    public function assignedWarehouseLocationIds(): array
    {
        if (! $this->isWarehouseStaff() && ! $this->isWarehousePic()) return [];

        return $this->location_id && Location::query()->whereKey($this->location_id)
            ->where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->exists()
            ? [(int) $this->location_id]
            : [];
    }

    /** @return array<int, int> */
    public function assignedLocationIds(): array
    {
        return $this->isWarehouseStaff() || $this->isWarehousePic()
            ? $this->assignedWarehouseLocationIds()
            : ($this->isOutletUser() ? $this->assignedOutletLocationIds() : []);
    }

    public function canAccessOutletLocation(int $locationId): bool
    {
        return in_array($locationId, $this->assignedOutletLocationIds(), true);
    }
}
