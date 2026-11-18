<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingReceiptWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $warehouseStaff;

    private Supplier $supplier;

    private Product $product;

    private PurchaseOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->warehouseStaff = User::factory()->create();
        $this->warehouseStaff->roles()->attach(Role::where('name', 'staff_gudang')->firstOrFail());
        $this->supplier = Supplier::create(['name' => 'Supplier Receipt Test', 'is_active' => true]);
        $category = Category::create(['name' => 'Kategori Receipt Test', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Unit Receipt Test', 'symbol' => 'urt', 'is_active' => true]);
        $this->product = Product::create([
            'sku' => 'RECEIPT-001',
            'name' => 'Produk Receipt Test',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $location = Location::create(['code' => 'WH-RECEIPT', 'name' => 'Gudang Receipt Test', 'type' => 'warehouse', 'is_active' => true]);
        $this->warehouseStaff->update(['location_id' => $location->id]);
        $request = PurchaseRequest::create([
            'reference' => 'PR-RECEIPT-TEST',
            'requested_by' => $this->warehouseStaff->id,
            'status' => 'ordered',
        ]);
        $this->order = PurchaseOrder::create([
            'reference' => 'PO-RECEIPT-TEST',
            'purchase_request_id' => $request->id,
            'supplier_id' => $this->supplier->id,
            'location_id' => $location->id,
            'created_by' => $this->warehouseStaff->id,
            'status' => 'issued',
        ]);
        $this->order->items()->create(['product_id' => $this->product->id, 'quantity' => 5]);
    }

    public function test_partial_receipt_without_invoice_is_audited_and_replayed_submit_does_not_duplicate_stock(): void
    {
        $key = (string) str()->uuid();
        $payload = $this->receiptPayload($key, accepted: '2.000', delivered: '3.000', note: '1 unit rusak');

        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $payload)
            ->assertRedirect(route('purchasing.orders.show', $this->order));

        $receipt = StockIn::firstOrFail();
        $this->assertNull($receipt->purchase_invoice_id);
        $this->assertSame($key, $receipt->idempotency_key);
        $this->assertSame('2.000', $receipt->items()->firstOrFail()->quantity);
        $this->assertSame('3.000', $receipt->items()->firstOrFail()->delivered_quantity);
        $this->assertSame('issued', $this->order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', [
            'stock_in_item_id' => $receipt->items()->firstOrFail()->id,
            'type' => 'in',
            'quantity' => '2.000',
        ]);

        // A browser/network retry carries the original key and resolves to the saved receipt.
        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $payload)
            ->assertRedirect(route('purchasing.orders.show', $this->order));

        $this->assertDatabaseCount('stock_ins', 1);
        $this->assertDatabaseCount('stock_in_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $this->actingAs($this->warehouseStaff)
            ->get(route('purchasing.orders.show', $this->order))
            ->assertOk()
            ->assertSee('Faktur belum ditautkan')
            ->assertSee('Invoice pending');
    }

    public function test_receipt_requires_a_valid_unique_submission_key(): void
    {
        $payload = $this->receiptPayload('not-a-uuid', accepted: '1.000', delivered: '1.000');

        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $payload)
            ->assertSessionHasErrors('idempotency_key');

        $this->assertDatabaseCount('stock_ins', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_unfit_goods_are_recorded_without_increasing_stock(): void
    {
        $payload = $this->receiptPayload((string) str()->uuid(), accepted: '0.000', delivered: '1.000', note: 'Seluruh unit rusak saat tiba; ditolak');

        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $payload)
            ->assertRedirect(route('purchasing.orders.show', $this->order));

        $receipt = StockIn::firstOrFail();
        $this->assertSame('0.000', $receipt->items()->firstOrFail()->quantity);
        $this->assertSame('1.000', $receipt->items()->firstOrFail()->delivered_quantity);
        $this->assertSame('issued', $this->order->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_full_receipt_closes_po_and_documented_overdelivery_is_auditable(): void
    {
        $payload = $this->receiptPayload((string) str()->uuid(), accepted: '6.000', delivered: '6.000', note: 'Supplier mengirim 1 unit ekstra; disetujui Purchasing via telp');

        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $payload)
            ->assertRedirect(route('purchasing.orders.show', $this->order));

        $this->assertSame('received', $this->order->fresh()->status);
        $this->assertSame('6.000', StockIn::firstOrFail()->items()->firstOrFail()->quantity);
        $this->assertSame('Supplier mengirim 1 unit ekstra; disetujui Purchasing via telp', StockIn::firstOrFail()->items()->firstOrFail()->variance_note);
        $this->assertDatabaseHas('stock_movements', ['type' => 'in', 'quantity' => '6.000']);
    }

    public function test_invoice_can_be_linked_to_pending_receipt_without_creating_another_movement(): void
    {
        $this->actingAs($this->warehouseStaff)
            ->post(route('purchasing.orders.receive', $this->order), $this->receiptPayload((string) str()->uuid(), accepted: '5.000', delivered: '5.000'));
        $receipt = StockIn::firstOrFail();

        $purchasing = User::factory()->create();
        $purchasing->roles()->attach(Role::where('name', 'purchasing')->firstOrFail());
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->order->id,
            'invoice_number' => 'INV-RECEIPT-TEST',
            'invoice_number_key' => 'INV-RECEIPT-TEST',
            'invoice_date' => today(),
            'document_total' => 500,
            'document_path' => 'private/test-invoice.pdf',
            'document_name' => 'test-invoice.pdf',
            'status' => 'draft',
            'created_by' => $purchasing->id,
        ]);
        $invoice->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 100,
        ]);

        $this->actingAs($purchasing)
            ->post(route('purchase-invoices.receipts.link', $invoice), ['stock_in_ids' => [$receipt->id]])
            ->assertRedirect();

        $this->assertSame($invoice->id, $receipt->fresh()->purchase_invoice_id);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame('5.000', $receipt->items()->firstOrFail()->quantity);
    }

    private function receiptPayload(string $key, string $accepted, string $delivered, ?string $note = null): array
    {
        return [
            'idempotency_key' => $key,
            'received_at' => today()->toDateString(),
            'items' => [
                $this->order->items()->firstOrFail()->id => [
                    'accepted_quantity' => $accepted,
                    'delivered_quantity' => $delivered,
                    'variance_note' => $note,
                ],
            ],
        ];
    }
}
