<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_open_user_and_role_management(): void
    {
        $staff = $this->userWithRole('staff_gudang');
        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.roles.index'))->assertForbidden();

        $admin = $this->userWithRole('super_admin');
        $this->assertTrue($admin->hasPermissionTo('users.manage'));
        $this->assertTrue($admin->is_active);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('account'))->assertSee('Kelola Pengguna');
        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertSee('PIC Outlet')->assertSee('Management');
    }

    public function test_super_admin_can_create_user_with_assigned_role_and_audit_entry(): void
    {
        $admin = $this->userWithRole('super_admin');
        $role = Role::where('name', 'purchasing')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Ayu Purchasing',
            'email' => 'ayu@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
            'role_ids' => [$role->id],
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'ayu@example.com')->firstOrFail();
        $this->assertSame('Ayu Purchasing', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('purchasing'));
        $this->assertTrue(password_verify('secure-password-123', $user->password));
        $this->assertDatabaseHas('administration_audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.created',
            'subject_id' => $user->id,
        ]);
    }

    public function test_super_admin_can_assign_the_new_confirmed_roles_without_admin_permission(): void
    {
        $admin = $this->userWithRole('super_admin');

        foreach ([
            'pic_outlet' => ['name' => 'PIC Outlet User', 'email' => 'pic@example.com'],
            'management' => ['name' => 'Management User', 'email' => 'management@example.com'],
        ] as $roleName => $attributes) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $this->actingAs($admin)->post(route('admin.users.store'), $attributes + [
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
                'role_ids' => [$role->id],
            ])->assertRedirect(route('admin.users.index'));

            $user = User::where('email', $attributes['email'])->firstOrFail();
            $this->assertTrue($user->hasRole($roleName));
            $this->assertTrue($user->hasPermissionTo('app.access'));
            $this->assertFalse($user->hasPermissionTo('users.manage'));
            $this->actingAs($user)->get('/dashboard')->assertOk();
        }
    }

    public function test_duplicate_email_and_invalid_role_are_rejected(): void
    {
        $admin = $this->userWithRole('super_admin');
        User::factory()->create(['email' => 'used@example.com']);

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'Duplicate',
            'email' => 'used@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
            'role_ids' => [99999],
        ])->assertRedirect(route('admin.users.create'))->assertSessionHasErrors(['email', 'role_ids.0']);
    }

    public function test_super_admin_can_edit_user_and_change_roles(): void
    {
        $admin = $this->userWithRole('super_admin');
        $user = $this->userWithRole('staff_gudang');
        $finance = Role::where('name', 'finance')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Staff Finance',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [$finance->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertOk();

        $user->refresh();
        $this->assertSame('Staff Finance', $user->name);
        $this->assertTrue($user->hasRole('finance'));
        $this->assertDatabaseHas('administration_audit_logs', [
            'action' => 'user.updated',
            'subject_id' => $user->id,
        ]);
    }

    public function test_deactivated_users_cannot_log_in_or_continue_using_existing_session(): void
    {
        $admin = $this->userWithRole('super_admin');
        $user = $this->userWithRole('staff_gudang', ['email' => 'inactive@example.com', 'password' => 'secure-password-123']);

        $this->actingAs($admin)->patch(route('admin.users.status', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('administration_audit_logs', [
            'action' => 'user.deactivated',
            'subject_id' => $user->id,
        ]);

        $this->actingAs($user->fresh())->get('/dashboard')->assertForbidden();

        auth()->logout();
        $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'secure-password-123',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_user_can_be_soft_deleted_and_restored(): void
    {
        $admin = $this->userWithRole('super_admin');
        $user = $this->userWithRole('finance');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->patch(route('admin.users.restore', $user->id))->assertRedirect();
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('administration_audit_logs', [
            'action' => 'user.deleted',
            'subject_id' => $user->id,
        ]);
        $this->assertDatabaseHas('administration_audit_logs', [
            'action' => 'user.restored',
            'subject_id' => $user->id,
        ]);
    }

    public function test_role_permission_matrix_updates_and_keeps_admin_permission_locked(): void
    {
        $admin = $this->userWithRole('super_admin');
        $staffRole = Role::where('name', 'staff_gudang')->firstOrFail();
        $appAccess = $staffRole->permissions()->where('name', 'app.access')->firstOrFail();
        $adminPermission = $staffRole->permissions()->where('name', 'users.manage')->first();

        $this->actingAs($admin)->patch(route('admin.roles.permissions.update', $staffRole), [
            'permissions' => [],
        ])->assertRedirect();

        $this->assertFalse($staffRole->fresh()->permissions()->where('name', 'app.access')->exists());
        $this->assertNull($adminPermission);
        $staffUser = User::factory()->create();
        $staffUser->roles()->attach($staffRole);
        $this->actingAs($staffUser->fresh())->get('/dashboard')->assertForbidden();
        $this->assertDatabaseHas('administration_audit_logs', [
            'action' => 'role.permissions_updated',
            'subject_id' => $staffRole->id,
        ]);

        $adminRole = Role::where('name', 'super_admin')->firstOrFail();
        $usersManageId = $adminRole->permissions()->where('name', 'users.manage')->value('permissions.id');
        $this->actingAs($admin)->patch(route('admin.roles.permissions.update', $adminRole), [
            'permissions' => [$usersManageId],
        ])->assertSessionHasErrors('permissions.0');

        $this->assertTrue($adminRole->fresh()->permissions()->where('name', 'users.manage')->exists());
        $this->assertNotNull($appAccess);
    }

    public function test_seeder_does_not_overwrite_a_permission_matrix_change(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $staffRole = Role::where('name', 'staff_gudang')->firstOrFail();
        $staffRole->permissions()->detach($staffRole->permissions()->where('name', 'app.access')->firstOrFail());

        $this->seed(RolePermissionSeeder::class);

        $this->assertFalse($staffRole->fresh()->permissions()->where('name', 'app.access')->exists());
        $this->assertFalse($staffRole->fresh()->permissions()->where('name', 'users.manage')->exists());
    }

    public function test_admin_cannot_remove_own_role_or_disable_own_account(): void
    {
        $admin = $this->userWithRole('super_admin');
        $staffRole = Role::where('name', 'staff_gudang')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [$staffRole->id],
        ])->assertSessionHasErrors('role_ids');

        $this->patch(route('admin.users.status', $admin))->assertSessionHasErrors('user');
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole('super_admin'));
    }

    public function test_last_active_super_admin_cannot_be_deactivated_or_deleted(): void
    {
        $admin = $this->userWithRole('super_admin');
        $manager = $this->userWithRole('finance');
        $managePermission = Permission::where('name', 'users.manage')->firstOrFail();
        Role::where('name', 'finance')->firstOrFail()->permissions()->attach($managePermission);

        $this->actingAs($manager)->patch(route('admin.users.status', $admin))
            ->assertSessionHasErrors('user');
        $this->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user->fresh();
    }
}
