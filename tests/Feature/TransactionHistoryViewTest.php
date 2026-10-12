<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_history_index_renders_informative_kpis_filters_and_records(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());

        $category = Category::create(['name' => 'Bahan Baku', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        $location = Location::create(['code' => 'WH-01', 'name' => 'Gudang Utama', 'type' => 'warehouse', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-TH-01',
            'name' => 'Tepung Terigu Segitiga',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $stockIn = StockIn::create([
            'reference' => 'GR-HIST-001',
            'source' => 'Pemasok Gandum',
            'location_id' => $location->id,
            'received_by' => $user->id,
            'received_at' => now(),
            'notes' => 'Penerimaan terigu',
        ]);
        StockInItem::create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $stockOut = StockOut::create([
            'reference' => 'DO-HIST-002',
            'destination' => 'Kitchen Pastry',
            'location_id' => $location->id,
            'issued_by' => $user->id,
            'issued_at' => now(),
            'notes' => 'Pengeluaran terigu',
        ]);
        StockOutItem::create([
            'stock_out_id' => $stockOut->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->actingAs($user)->get(route('inventory.transactions.index'))
            ->assertOk()
            ->assertSee('Riwayat Transaksi')
            ->assertSee('Total Seluruh Dokumen')
            ->assertSee('GR-HIST-001')
            ->assertSee('DO-HIST-002')
            ->assertSee('Stock In')
            ->assertSee('Stock Out');

        $this->actingAs($user)->get(route('inventory.transactions.show', ['stock_in', $stockIn->id]))
            ->assertOk()
            ->assertSee('GR-HIST-001')
            ->assertSee('Tepung Terigu Segitiga');
    }
}
