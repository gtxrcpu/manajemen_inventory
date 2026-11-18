<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryReportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOutPurposeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Location $outlet;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->staff = User::factory()->create();
        $this->staff->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());
        $this->outlet = Location::create(['code' => 'OT-01', 'name' => 'Outlet Uji', 'type' => 'outlet', 'is_active' => true]);
        Location::create(['code' => 'WH-01', 'name' => 'Gudang Uji', 'type' => 'warehouse', 'is_active' => true]);
        $category = Category::create(['name' => 'Bahan Uji', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        $this->product = Product::create(['sku' => 'SO-001', 'name' => 'Produk Uji', 'category_id' => $category->id, 'unit_id' => $unit->id, 'is_active' => true]);
        StockMovement::create(['product_id' => $this->product->id, 'location_id' => $this->outlet->id, 'user_id' => $this->staff->id, 'type' => 'in', 'quantity' => 10, 'note' => 'Saldo awal test']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'reference' => 'SO-TEST-001',
            'destination' => 'Pelanggan outlet',
            'purpose' => 'sale',
            'location_id' => $this->outlet->id,
            'issued_at' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ], $overrides);
    }

    public function test_new_stock_out_requires_a_category(): void
    {
        $this->actingAs($this->staff)->post(route('inventory.stock-out.store'), $this->payload(['purpose' => '']))
            ->assertSessionHasErrors('purpose');
        $this->assertDatabaseCount('stock_outs', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_other_category_requires_a_reason_and_transfer_destination_uses_transfer_workflow(): void
    {
        $this->actingAs($this->staff)->post(route('inventory.stock-out.store'), $this->payload(['purpose' => 'other']))
            ->assertSessionHasErrors('notes');

        $this->actingAs($this->staff)->post(route('inventory.stock-out.store'), $this->payload([
            'reference' => 'SO-TEST-002',
            'destination' => 'Gudang Uji',
        ]))->assertSessionHasErrors('destination');

        $this->assertDatabaseCount('stock_outs', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_stock_out_keeps_category_in_ledger_audit_detail_history_and_report(): void
    {
        $this->actingAs($this->staff)->post(route('inventory.stock-out.store'), $this->payload())->assertRedirect();

        $this->assertDatabaseHas('stock_outs', ['reference' => 'SO-TEST-001', 'purpose' => 'sale']);
        $this->assertDatabaseHas('stock_movements', ['type' => 'out', 'quantity' => 2, 'location_id' => $this->outlet->id]);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'stock_out.issued']);

        $issue = \App\Models\StockOut::where('reference', 'SO-TEST-001')->firstOrFail();
        $this->actingAs($this->staff)->get(route('inventory.stock-out.show', $issue))->assertOk()->assertSee('Penjualan outlet');
        $this->actingAs($this->staff)->get(route('inventory.transactions.index'))->assertOk()->assertSee('Penjualan outlet');

        $report = app(InventoryReportService::class)->query('stock_out', ['location_id' => $this->outlet->id], $this->staff)->first();
        $this->assertSame('Penjualan outlet', $report->purpose);
    }
}
