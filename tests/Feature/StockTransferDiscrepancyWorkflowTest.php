<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryValuationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferDiscrepancyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $pic;
    private User $purchasing;
    private User $warehousePic;
    private Location $warehouse;
    private Location $outlet;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->staff = $this->userWithRole('staff_gudang');
        $this->purchasing = $this->userWithRole('purchasing');
        $this->warehouse = Location::create(['code' => 'WH-TRF', 'name' => 'Gudang Transfer', 'type' => 'warehouse', 'is_active' => true]);
        $this->staff->update(['location_id' => $this->warehouse->id]);
        $this->outlet = Location::create(['code' => 'OT-TRF', 'name' => 'Outlet Transfer', 'type' => 'outlet', 'is_active' => true]);
        $this->pic = $this->userWithRole('pic_outlet');
        $this->pic->update(['location_id' => $this->outlet->id]);
        $this->warehousePic = $this->userWithRole('pic_warehouse');
        $this->warehousePic->update(['location_id' => $this->warehouse->id]);
        $category = Category::create(['name' => 'Kategori Transfer', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Unit Transfer', 'symbol' => 'ut', 'is_active' => true]);
        $this->product = Product::create(['sku' => 'TRF-001', 'name' => 'Produk Transfer', 'category_id' => $category->id, 'unit_id' => $unit->id, 'is_active' => true]);
        StockMovement::create(['product_id' => $this->product->id, 'location_id' => $this->warehouse->id, 'user_id' => $this->staff->id, 'type' => 'in', 'quantity' => 20, 'note' => 'Saldo awal transfer test']);
    }

    private function userWithRole(string $name): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $name)->firstOrFail());
        return $user;
    }

    private function dispatch(string $reference): StockTransfer
    {
        $this->actingAs($this->staff)->post(route('inventory.transfers.store'), [
            'reference' => $reference,
            'source_location_id' => $this->warehouse->id,
            'destination_location_id' => $this->outlet->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
        ])->assertRedirect();

        $transfer = StockTransfer::where('reference', $reference)->firstOrFail();
        $this->assertTrue($this->warehousePic->hasRole('pic_warehouse'));
        $this->assertTrue($this->warehousePic->hasPermissionTo('transfer.approve'));
        $this->assertTrue($this->warehousePic->hasPermissionTo('app.access'));
        $this->assertSame((int) $this->warehouse->id, (int) $this->warehousePic->location_id);
        $this->assertSame((int) $this->warehouse->id, (int) $transfer->source_location_id);
        $this->actingAs($this->warehousePic)->post(route('inventory.transfers.approve', $transfer))->assertRedirect();

        return $transfer->fresh();
    }

    public function test_partial_receipt_leaves_company_owned_quantity_in_transit_and_valued_separately(): void
    {
        $transfer = $this->dispatch('TRF-PARTIAL-001');
        $item = $transfer->items()->firstOrFail();

        $this->actingAs($this->pic)->post(route('inventory.transfers.receive', $transfer), [
            'items' => [$item->id => 2],
            'is_final' => false,
        ])->assertRedirect();

        $this->assertSame('partially_received', $transfer->fresh()->status);
        $this->assertSame('2.000', $item->fresh()->received_quantity);
        $transit = app(InventoryValuationService::class)->snapshot()->firstWhere('location_id', 'transit:'.$this->outlet->id);
        $this->assertNotNull($transit);
        $this->assertSame(3.0, $transit['quantity']);
        $this->assertSame('incomplete', $transit['valuation_status']);
        $this->actingAs($this->staff)->get(route('inventory.transfers.index'))
            ->assertOk()->assertSee('3 unit masih dalam kendali perusahaan');
        $finance = $this->userWithRole('finance');
        $this->actingAs($finance)->get(route('finance.valuation'))
            ->assertOk()->assertSee('Dalam perjalanan ke Outlet Transfer')->assertSee('TRF-PARTIAL-001');
        $export = $this->actingAs($finance)->get(route('finance.valuation.export'))->assertOk();
        $this->assertStringContainsString('TRF-PARTIAL-001', $export->streamedContent());
    }

    public function test_final_short_receipt_stays_open_until_purchasing_records_each_resolution(): void
    {
        $transfer = $this->dispatch('TRF-DISC-001');
        $item = $transfer->items()->firstOrFail();

        $this->actingAs($this->pic)->post(route('inventory.transfers.receive', $transfer), [
            'items' => [$item->id => 2],
            'is_final' => true,
            'discrepancy_reason' => 'Dua unit tidak ditemukan saat bongkar.',
        ])->assertRedirect();

        $this->assertSame('discrepancy_open', $transfer->fresh()->status);
        $this->assertSame('3.000', $item->fresh()->discrepancy_quantity);
        $this->assertDatabaseCount('stock_movements', 3); // opening, dispatch out, accepted receipt in
        $this->actingAs($this->purchasing)->get(route('inventory.transfers.show', $transfer))
            ->assertOk()->assertSee('Discrepancy terbuka')->assertSee('Hilang di perjalanan');

        $this->actingAs($this->purchasing)->post(route('inventory.transfers.discrepancies.resolve', [$transfer, $item->id]), [
            'resolution_type' => 'lost', 'quantity' => 2, 'reason' => 'Hilang dikonfirmasi kurir; klaim dibuat.',
        ])->assertRedirect();
        $this->assertSame('discrepancy_open', $transfer->fresh()->status);
        $this->assertSame('1.000', $item->fresh()->discrepancy_quantity);

        $this->actingAs($this->purchasing)->post(route('inventory.transfers.discrepancies.resolve', [$transfer, $item->id]), [
            'resolution_type' => 'damaged', 'quantity' => 1, 'reason' => 'Sisa unit tiba pecah dan ditolak PIC.',
        ])->assertRedirect();

        $this->assertSame('received_with_discrepancy', $transfer->fresh()->status);
        $this->assertSame('0.000', $item->fresh()->discrepancy_quantity);
        $this->assertDatabaseCount('stock_transfer_discrepancy_resolutions', 2);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'stock_transfer.discrepancy_resolved']);
        $this->assertDatabaseCount('stock_movements', 3);
    }

    public function test_late_arrival_can_be_received_after_discrepancy_was_opened(): void
    {
        $transfer = $this->dispatch('TRF-LATE-001');
        $item = $transfer->items()->firstOrFail();
        $this->actingAs($this->pic)->post(route('inventory.transfers.receive', $transfer), [
            'items' => [$item->id => 2], 'is_final' => true, 'discrepancy_reason' => 'Tiga unit belum sampai saat pemeriksaan.',
        ])->assertRedirect();

        $this->actingAs($this->pic)->post(route('inventory.transfers.receive', $transfer), [
            'items' => [$item->id => 3], 'is_final' => false,
        ])->assertRedirect();

        $this->assertSame('received', $transfer->fresh()->status);
        $this->assertSame('5.000', $item->fresh()->received_quantity);
        $this->assertSame('0.000', $item->fresh()->discrepancy_quantity);
        $this->assertDatabaseCount('stock_transfer_receipts', 2);
    }
}
