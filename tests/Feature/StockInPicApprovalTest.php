<?php

namespace Tests\Feature;

use App\Models\AdministrationAuditLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInPicApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Location $outlet;

    private Location $otherOutlet;

    private Product $product;

    private User $staff;

    private User $pic;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->outlet = Location::create(['code' => 'OUT-01', 'name' => 'Outlet Satu', 'type' => 'outlet', 'is_active' => true]);
        $this->otherOutlet = Location::create(['code' => 'OUT-02', 'name' => 'Outlet Dua', 'type' => 'outlet', 'is_active' => true]);
        $this->staff = $this->userWithRole('staff_gudang');
        $this->pic = $this->userWithRole('pic_outlet', $this->outlet);
        $this->admin = $this->userWithRole('super_admin');

        $category = Category::create(['name' => 'Bahan Baku', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);
        $this->product = Product::create([
            'sku' => 'TEST-001', 'name' => 'Ayam Uji', 'category_id' => $category->id,
            'unit_id' => $unit->id, 'is_active' => true,
        ]);
    }

    public function test_outlet_stock_in_waits_for_pic_approval_before_posting_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->outlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();

        $response->assertRedirect(route('inventory.stock-in.show', $receipt));
        $this->assertSame('pending_approval', $receipt->status);
        $this->assertDatabaseMissing('stock_movements', ['stock_in_item_id' => $receipt->items()->value('id')]);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'stock_in.submitted_for_approval', 'subject_id' => $receipt->id]);
    }

    public function test_only_pic_assigned_to_outlet_can_see_and_decide_its_pending_stock_in(): void
    {
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->outlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();
        $otherPic = $this->userWithRole('pic_outlet', $this->otherOutlet);
        $otherPayload = $this->payload($this->otherOutlet);
        $otherPayload['reference'] = 'GR-PIC-002';
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $otherPayload);
        $otherReceipt = StockIn::where('reference', 'GR-PIC-002')->firstOrFail();
        $this->actingAs($otherPic)->post(route('inventory.stock-in.approve', $otherReceipt));
        $otherMovement = StockMovement::where('stock_in_item_id', $otherReceipt->items()->value('id'))->firstOrFail();

        $this->actingAs($this->pic)->get(route('inventory.stock-in.index'))->assertOk()->assertSee('GR-PIC-001');
        $this->actingAs($this->pic)->get(route('inventory.stock-in.show', $receipt))->assertOk()->assertSee('Setujui penerimaan');
        $this->actingAs($otherPic)->get(route('inventory.stock-in.index'))->assertOk()->assertDontSee('GR-PIC-001');
        $this->actingAs($otherPic)->get(route('inventory.stock-in.show', $receipt))->assertForbidden();
        $this->actingAs($otherPic)->get(route('inventory.transactions.show', ['type' => 'stock_in', 'id' => $receipt->id]))->assertForbidden();
        $this->actingAs($this->pic)->get(route('inventory.movements.show', $otherMovement))->assertForbidden();
        $this->actingAs($otherPic)->post(route('inventory.stock-in.approve', $receipt))->assertForbidden();
        $this->assertSame('pending_approval', $receipt->fresh()->status);
    }

    public function test_approval_posts_stock_once_and_retains_decision_audit(): void
    {
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->outlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();

        $this->actingAs($this->pic)->post(route('inventory.stock-in.approve', $receipt))->assertRedirect(route('inventory.stock-in.show', $receipt));
        $this->assertSame('approved', $receipt->fresh()->status);
        $this->assertSame($this->pic->id, $receipt->fresh()->decision_by);
        $this->assertNotNull($receipt->fresh()->decided_at);
        $this->assertSame(5.0, (float) StockMovement::where('stock_in_item_id', $receipt->items()->value('id'))->sum('quantity'));

        $this->actingAs($this->pic)->post(route('inventory.stock-in.approve', $receipt));
        $this->actingAs($this->pic)->post(route('inventory.stock-in.reject', $receipt), ['rejection_reason' => 'Sudah diproses']);
        $this->assertSame(1, StockMovement::where('stock_in_item_id', $receipt->items()->value('id'))->count());
        $this->assertSame('approved', $receipt->fresh()->status);
        $audit = AdministrationAuditLog::where('action', 'stock_in.approved')->where('subject_id', $receipt->id)->firstOrFail();
        $this->assertSame($this->pic->id, $audit->actor_id);
        $this->assertSame('pending_approval', $audit->changes['status_before']);
        $this->assertSame('approved', $audit->changes['status_after']);
    }

    public function test_rejection_requires_reason_and_never_posts_stock(): void
    {
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->outlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();

        $this->actingAs($this->pic)->post(route('inventory.stock-in.reject', $receipt), [])->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending_approval', $receipt->fresh()->status);

        $this->actingAs($this->pic)->post(route('inventory.stock-in.reject', $receipt), ['rejection_category' => 'quantity_mismatch', 'rejection_reason' => 'Jumlah fisik tidak sesuai']);
        $this->assertSame('rejected', $receipt->fresh()->status);
        $this->assertSame('Jumlah fisik tidak sesuai', $receipt->fresh()->rejection_reason);
        $this->assertSame(0, StockMovement::where('stock_in_item_id', $receipt->items()->value('id'))->count());
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'stock_in.rejected', 'actor_id' => $this->pic->id, 'subject_id' => $receipt->id]);
    }

    public function test_staff_and_unrelated_roles_cannot_approve_stock_in(): void
    {
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->outlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();
        $purchasing = $this->userWithRole('purchasing');

        $this->actingAs($this->staff)->post(route('inventory.stock-in.approve', $receipt))->assertForbidden();
        $this->actingAs($purchasing)->post(route('inventory.stock-in.approve', $receipt))->assertForbidden();
        $this->assertSame('pending_approval', $receipt->fresh()->status);
    }

    public function test_warehouse_stock_in_keeps_existing_immediate_posting_workflow(): void
    {
        $warehouse = Location::create(['code' => 'WH-01', 'name' => 'Gudang Pusat', 'type' => 'warehouse', 'is_active' => true]);
        $payload = $this->payload($warehouse);
        $payload['reference'] = 'GR-WH-001';

        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $payload)->assertRedirect();
        $receipt = StockIn::where('reference', 'GR-WH-001')->firstOrFail();

        $this->assertSame('approved', $receipt->status);
        $this->assertSame(1, StockMovement::where('stock_in_item_id', $receipt->items()->value('id'))->count());
    }

    public function test_super_admin_can_review_outlet_stock_in_under_admin_permission(): void
    {
        $this->actingAs($this->admin)->post(route('inventory.stock-in.store'), $this->payload($this->otherOutlet));
        $receipt = StockIn::where('reference', 'GR-PIC-001')->firstOrFail();
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('inventory.stock-in.approve', $receipt))->assertRedirect();
        $this->assertSame('approved', $receipt->fresh()->status);
        $this->assertSame($admin->id, $receipt->fresh()->decision_by);
    }

    private function userWithRole(string $roleName, ?Location $location = null): User
    {
        $user = User::factory()->create(['location_id' => $location?->id]);
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user;
    }

    private function payload(Location $location): array
    {
        return [
            'reference' => 'GR-PIC-001', 'source' => 'Pembelian', 'location_id' => $location->id,
            'received_at' => today()->toDateString(), 'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
        ];
    }
}
