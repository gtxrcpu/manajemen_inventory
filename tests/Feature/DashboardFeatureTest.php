<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_dashboard_uses_role_specific_actions_and_real_master_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $category = Category::create(['name' => 'Bahan', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        Product::create(['sku' => 'TL-001', 'name' => 'Beras', 'category_id' => $category->id, 'unit_id' => $unit->id, 'is_active' => true]);
        Location::create(['code' => 'WH-01', 'name' => 'Gudang Utama', 'type' => 'warehouse', 'is_active' => true]);

        $roleActions = [
            'staff_gudang' => ['Barang Masuk', 'Barang Keluar'],
            'pic_outlet' => ['Stok Outlet', 'Terima Barang'],
            'management' => [],
            'purchasing' => ['Pantau Gudang', 'Review Opname'],
            'finance' => ['Stok Seluruh Gudang', 'Valuasi Persediaan'],
        ];

        foreach (array_keys($roleActions) as $roleName) {
            $user = User::factory()->create(['name' => 'Ayu '.str($roleName)->headline()]);
            $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

            $response = $this->actingAs($user)->get('/dashboard')
                ->assertOk()
                ->assertSee('Ayu '.str($roleName)->headline())
                ->assertSee('1 Produk')
                ->assertSee('1 lokasi aktif')
                ->assertSee('Saldo stok belum dicatat')
                ->assertDontSee('Daging Sapi Wagyu')
                ->assertDontSee('Tambah Pengguna');

            foreach ($roleActions[$roleName] as $action) {
                $response->assertSee($action);
            }
        }
    }

    public function test_super_admin_uses_existing_dashboard_layout_with_admin_content_and_add_user_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['name' => 'Super Admin Utama']);
        $admin->roles()->attach(Role::where('name', 'super_admin')->firstOrFail());

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Administrasi Sistem')
            ->assertSee('PENGGUNA AKTIF')
            ->assertDontSee('Fokus Staff Gudang');
    }

    public function test_login_page_shows_background_image_and_security_badge(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('login.webp', false)
            ->assertSee('Akses Terenkripsi SSL');
    }
}
