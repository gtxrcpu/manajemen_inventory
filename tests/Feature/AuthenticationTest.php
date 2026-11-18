<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke Sistem Inventory')
            ->assertSee('name="email"', false);
    }

    public function test_user_can_sign_in_with_valid_credentials(): void
    {
        $user = $this->createAccessibleUser([
            'email' => 'staff@example.com',
            'password' => 'correct-password',
        ]);

        $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'correct-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->createAccessibleUser([
            'email' => 'staff@example.com',
            'password' => 'correct-password',
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertSame('Email atau kata sandi belum cocok. Coba periksa lagi, ya.', session('errors')->first('email'));

        $this->assertGuest();
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertRedirect('/login')->assertSessionHasErrors(['email', 'password']);

        $this->assertSame('Format emailnya belum benar. Coba cek lagi, ya.', session('errors')->first('email'));
        $this->assertSame('Kata sandinya belum diisi.', session('errors')->first('password'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_application_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/inventory')->assertRedirect('/login');
    }

    public function test_authenticated_user_without_permission_gets_forbidden_response(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_account_page_exposes_logout_action(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('Keluar')
            ->assertSee(route('logout'));
    }

    public function test_all_confirmed_roles_receive_the_shared_application_access_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['super_admin', 'staff_gudang', 'staff_outlet', 'pic_outlet', 'pic_warehouse', 'management', 'purchasing', 'finance'] as $roleName) {
            $user = User::factory()->create();
            $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

            $this->assertTrue($user->fresh()->hasPermissionTo('app.access'));
            $this->actingAs($user)->get('/dashboard')->assertOk();
        }
    }

    public function test_database_seeder_creates_the_local_admin_access_baseline(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->hasPermissionTo('app.access'));
    }

    private function createAccessibleUser(array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());

        return $user;
    }
}
