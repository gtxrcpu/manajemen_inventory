<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInAndOutViewTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());
        return $user;
    }

    public function test_stock_in_index_renders_informative_kpis_filters_and_records(): void
    {
        $user = $this->staffUser();
        $category = \App\Models\Category::create(['name' => 'Bahan Baku', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        $location = Location::create(['code' => 'WH-01', 'name' => 'Gudang Pusat', 'type' => 'warehouse', 'is_active' => true]);
        $user->update(['location_id' => $location->id]);
        $supplier = Supplier::create(['name' => 'PT Sumber Pangan', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Daging Ayam',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $stockIn = StockIn::create([
            'reference' => 'GR-TEST-001',
            'supplier_id' => $supplier->id,
            'source' => 'Vendor',
            'location_id' => $location->id,
            'received_by' => $user->id,
            'received_at' => now(),
            'notes' => 'Pengiriman perdana',
        ]);

        StockInItem::create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
            'quantity' => 15.5,
            'batch_number' => 'BCH-88',
        ]);

        $this->actingAs($user)->get(route('inventory.stock-in.index'))
            ->assertOk()
            ->assertSee('Penerimaan Barang (Stock In)')
            ->assertSee('Total Dokumen')
            ->assertSee('GR-TEST-001')
            ->assertSee('PT Sumber Pangan')
            ->assertSee('Gudang Pusat')
            ->assertSee('Daging Ayam');
    }

    public function test_stock_out_index_renders_informative_kpis_filters_and_records(): void
    {
        $user = $this->staffUser();
        $category = \App\Models\Category::create(['name' => 'Bahan Baku', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        $location = Location::create(['code' => 'WH-02', 'name' => 'Gudang Dapur', 'type' => 'warehouse', 'is_active' => true]);
        $user->update(['location_id' => $location->id]);
        $product = Product::create([
            'sku' => 'SKU-002',
            'name' => 'Beras Pandan Wangi',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $stockOut = StockOut::create([
            'reference' => 'DO-TEST-001',
            'destination' => 'Kitchen Bar',
            'location_id' => $location->id,
            'issued_by' => $user->id,
            'issued_at' => now(),
            'notes' => 'Pengeluaran harian dapur',
        ]);

        StockOutItem::create([
            'stock_out_id' => $stockOut->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $this->actingAs($user)->get(route('inventory.stock-out.index'))
            ->assertOk()
            ->assertSee('Pengeluaran Barang (Stock Out)')
            ->assertSee('Total Dokumen')
            ->assertSee('DO-TEST-001')
            ->assertSee('Kitchen Bar')
            ->assertSee('Gudang Dapur')
            ->assertSee('Beras Pandan Wangi');
    }
}
