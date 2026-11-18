<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\StockDisposal;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemWorkflowCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Location $warehouse;
    private Location $outlet;
    private Location $otherOutlet;
    private Product $product;
    private User $admin;
    private User $staff;
    private User $purchasing;
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->warehouse = Location::create(['code' => 'WH-COVER', 'name' => 'Gudang Coverage', 'type' => 'warehouse', 'is_active' => true]);
        $this->outlet = Location::create(['code' => 'OT-COVER', 'name' => 'Outlet Coverage', 'type' => 'outlet', 'is_active' => true]);
        $this->otherOutlet = Location::create(['code' => 'OT-COVER-2', 'name' => 'Outlet Coverage 2', 'type' => 'outlet', 'is_active' => true]);
        $category = Category::create(['name' => 'Kategori Coverage', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Unit Coverage', 'symbol' => 'uc', 'is_active' => true]);
        $this->product = Product::create(['sku' => 'COVER-001', 'name' => 'Produk Coverage', 'category_id' => $category->id, 'unit_id' => $unit->id, 'requires_expiry_tracking' => false, 'is_active' => true]);
        $this->admin = $this->user('super_admin');
        $this->staff = $this->user('staff_gudang', $this->warehouse);
        $this->purchasing = $this->user('purchasing');
        $this->finance = $this->user('finance');
    }

    public function test_opname_requires_independent_review_and_adjustment_writes_one_ledger_movement(): void
    {
        $this->openingStock($this->warehouse, 10);

        $this->actingAs($this->staff)->post(route('inventory.stock-opname.start'), [
            'location_id' => $this->warehouse->id,
            'period_type' => 'weekly',
            'count_scope' => 'full',
        ])->assertRedirect();
        $opname = StockOpname::firstOrFail();
        $item = $opname->items()->firstOrFail();

        $this->actingAs($this->staff)->post(route('inventory.stock-opname.submit', $opname), [
            'items' => [$item->id => ['physical_quantity' => 8, 'reason' => 'Dua unit rusak saat hitung']],
        ])->assertRedirect();
        $this->actingAs($this->purchasing)->post(route('inventory.stock-opname.approve', $opname), ['review_note' => 'Hasil diverifikasi'])->assertRedirect();

        $this->assertSame('approved', $opname->fresh()->status);
        $this->assertSame(1, StockMovement::where('stock_opname_item_id', $item->id)->count());
        $this->assertSame(-2.0, (float) $item->fresh()->adjustment_quantity);

        $this->actingAs($this->admin)->post(route('inventory.stock-adjustments.store'), [
            'reference' => 'ADJ-COVER-001', 'product_id' => $this->product->id,
            'location_id' => $this->warehouse->id, 'direction' => 'increase',
            'quantity' => 3, 'reason' => 'Koreksi hitung fisik',
        ])->assertRedirect();
        $adjustment = StockAdjustment::where('reference', 'ADJ-COVER-001')->firstOrFail();
        $this->assertDatabaseHas('stock_movements', ['stock_adjustment_id' => $adjustment->id, 'type' => 'in', 'quantity' => 3]);
    }

    public function test_disposal_quarantines_stock_until_a_different_approver_accepts_it(): void
    {
        $this->openingStock($this->warehouse, 10);

        $this->actingAs($this->staff)->post(route('inventory.stock-disposals.store'), [
            'reference' => 'DSP-COVER-001', 'type' => 'write_off', 'location_id' => $this->warehouse->id,
            'reason_category' => 'damaged', 'reason' => 'Kemasan rusak dan tidak layak pakai',
            'product_id' => $this->product->id, 'quantity' => 2,
        ])->assertRedirect();
        $disposal = StockDisposal::where('reference', 'DSP-COVER-001')->firstOrFail();

        $this->assertSame('pending_approval', $disposal->status);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->actingAs($this->purchasing)->post(route('inventory.stock-disposals.approve', $disposal), ['review_note' => 'Disetujui'])->assertRedirect();

        $this->assertSame('approved', $disposal->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', ['stock_disposal_item_id' => $disposal->items()->value('id'), 'type' => 'out', 'quantity' => 2]);
    }

    public function test_low_stock_reporting_export_and_search_pages_work_for_permitted_roles(): void
    {
        $this->actingAs($this->admin)->put(route('low-stock.thresholds.save'), [
            'location_id' => $this->warehouse->id,
            'thresholds' => [$this->product->id => 5],
        ])->assertRedirect();

        $this->actingAs($this->staff)->get(route('low-stock.index'))->assertOk();
        $this->actingAs($this->finance)->get(route('reports.index', ['report' => 'stock']))->assertOk();
        $this->actingAs($this->finance)->get(route('reports.index', ['report' => 'expiry']))->assertOk();
        $this->actingAs($this->finance)->get(route('reports.export', ['report' => 'stock', 'format' => 'csv']))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($this->finance)->get(route('reports.export', ['report' => 'stock', 'format' => 'xlsx']))
            ->assertOk()->assertJsonStructure(['title', 'filename', 'headers', 'rows']);
        $this->actingAs($this->staff)->get(route('search.index', ['q' => 'Produk Coverage']))->assertOk()->assertSee('Produk Coverage');
    }

    public function test_whatsapp_settings_and_pic_substitute_assignment_can_be_reviewed_and_revoked(): void
    {
        $this->actingAs($this->admin)->get(route('admin.whatsapp-alerts.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.whatsapp-alerts.status'))->assertOk()->assertJsonStructure(['connection', 'qr']);

        $primary = $this->user('pic_outlet', $this->outlet);
        $substitute = $this->user('pic_outlet', $this->otherOutlet);
        $startsAt = now()->addDay()->startOfMinute();
        $endsAt = $startsAt->copy()->addDay();

        $this->actingAs($this->admin)->post(route('admin.pic-substitutes.store'), [
            'location_id' => $this->outlet->id,
            'primary_pic_user_id' => $primary->id,
            'substitute_pic_user_id' => $substitute->id,
            'starts_at' => $startsAt->toDateTimeLocalString(),
            'ends_at' => $endsAt->toDateTimeLocalString(),
            'notes' => 'Pengganti sementara untuk pengujian',
        ])->assertRedirect();
        $assignment = \App\Models\PicSubstituteAssignment::firstOrFail();
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'pic.substitute_assigned', 'subject_id' => $assignment->id]);
        $this->actingAs($this->admin)->post(route('admin.pic-substitutes.revoke', $assignment), ['reason' => 'Jadwal selesai'])->assertRedirect();
        $this->assertNotNull($assignment->fresh()->revoked_at);
    }

    private function user(string $roleName, ?Location $location = null): User
    {
        $user = User::factory()->create(['location_id' => $location?->id]);
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());
        return $user;
    }

    private function openingStock(Location $location, int $quantity): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $location->id,
            'user_id' => $this->staff->id,
            'type' => 'in',
            'quantity' => $quantity,
            'note' => 'Saldo pembuka black-box',
        ]);
    }
}
