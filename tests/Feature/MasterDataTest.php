<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_is_restricted_to_super_admin(): void
    {
        $staff = $this->userWithRole('staff_gudang');
        $this->actingAs($staff)->get(route('admin.master-data.index', 'products'))->assertForbidden();

        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin)->get(route('admin.master-data.index', 'products'))->assertOk();
        $this->actingAs($admin)->get(route('account'))->assertSee('Master Data');
    }

    public function test_super_admin_can_create_product_and_audit_is_recorded(): void
    {
        $admin = $this->userWithRole('super_admin');
        $category = Category::create(['name' => 'Bahan', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.master-data.store', 'products'), [
            'sku' => 'TL-001', 'name' => 'Beras', 'category_id' => $category->id, 'unit_id' => $unit->id,
            'description' => 'Beras putih', 'requires_expiry_tracking' => '0', 'is_active' => '1',
        ])->assertRedirect();

        $product = Product::where('sku', 'TL-001')->firstOrFail();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame($unit->id, $product->unit_id);
        $this->assertDatabaseHas('administration_audit_logs', ['actor_id' => $admin->id, 'action' => 'master_data.created', 'subject_id' => $product->id]);
        $this->actingAs($admin)->get(route('admin.master-data.show', ['products', $product->id]))->assertOk()->assertSee('Beras');
    }

    public function test_master_data_validation_search_filter_and_soft_delete(): void
    {
        $admin = $this->userWithRole('super_admin');
        $category = Category::create(['name' => 'Bahan', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $this->actingAs($admin)->from(route('admin.master-data.create', 'products'))->post(route('admin.master-data.store', 'products'), [
            'sku' => 'BAD', 'name' => 'Invalid', 'category_id' => 999, 'unit_id' => $unit->id, 'is_active' => 1,
        ])->assertSessionHasErrors('category_id');

        $product = Product::create(['sku' => 'TL-002', 'name' => 'Minyak', 'category_id' => $category->id, 'unit_id' => $unit->id, 'is_active' => false]);
        $this->actingAs($admin)->get(route('admin.master-data.index', ['products', 'q' => 'Minyak', 'status' => 'inactive']))->assertOk()->assertSee('Minyak');
        $this->actingAs($admin)->delete(route('admin.master-data.destroy', ['products', $product->id]))->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'master_data.deleted', 'subject_id' => $product->id]);
    }

    public function test_supplier_and_location_records_validate_and_render(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->actingAs($admin)->post(route('admin.master-data.store', 'suppliers'), [
            'name' => 'WRT', 'email' => 'invalid-email', 'is_active' => 1,
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)->post(route('admin.master-data.store', 'locations'), [
            'code' => 'LOC-1', 'name' => 'Gudang Utama', 'type' => 'warehouse', 'is_active' => 1,
        ])->assertRedirect();
        $this->actingAs($admin)->get(route('admin.master-data.index', ['locations', 'type' => 'warehouse']))->assertOk()->assertSee('Gudang Utama');
    }

    private function userWithRole(string $roleName): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user->fresh();
    }
}
