<?php

namespace Tests\Feature;

use App\Models\AdministrationAuditLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoicePayment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseInvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Product $product;

    private User $purchasing;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
        $this->supplier = Supplier::create(['name' => 'Supplier Uji', 'is_active' => true]);
        $category = Category::create(['name' => 'Bahan Uji', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram Uji', 'symbol' => 'kgu', 'is_active' => true]);
        $this->product = Product::create(['sku' => 'INV-001', 'name' => 'Produk Uji', 'category_id' => $category->id, 'unit_id' => $unit->id, 'is_active' => true]);
        $this->purchasing = $this->userWithRole('purchasing');
        $this->finance = $this->userWithRole('finance');
    }

    public function test_purchasing_saves_supplier_invoice_and_item_as_draft_without_creating_stock_or_payment(): void
    {
        $response = $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $this->payload('INV-100'));
        $invoice = PurchaseInvoice::firstOrFail();

        $response->assertRedirect(route('purchase-invoices.show', $invoice));
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('125000.00', $invoice->document_total);
        $this->assertSame('2.000', $invoice->items()->firstOrFail()->quantity);
        $this->assertSame('kgu', $invoice->items()->firstOrFail()->unit_label);
        $this->assertSame('62500.00', $invoice->items()->firstOrFail()->unit_price);
        Storage::disk('local')->assertExists($invoice->document_path);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'purchase_invoice.created', 'subject_id' => $invoice->id]);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseMissing('stock_ins', ['purchase_order_id' => $invoice->purchase_order_id]);
    }

    public function test_purchasing_form_and_invoice_list_render_while_other_roles_cannot_create_invoices(): void
    {
        $this->actingAs($this->purchasing)->get(route('purchase-invoices.create'))->assertOk()->assertSee('Dokumen faktur')->assertSee('Detail item');
        $this->actingAs($this->purchasing)->get(route('purchase-invoices.index'))->assertOk()->assertSee('Faktur Pembelian');
        $this->actingAs($this->finance)->get(route('purchase-invoices.create'))->assertForbidden();
        $this->actingAs($this->finance)->get(route('reports.index'))->assertOk()->assertSee(route('purchase-invoices.index'), false)->assertSee(route('finance.valuation'), false);
    }

    public function test_invoice_number_is_unique_per_supplier_case_insensitively(): void
    {
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $this->payload('INV-100'))->assertRedirect();
        $duplicate = $this->payload(' inv-100 ');
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $duplicate)->assertSessionHasErrors('invoice_number');

        $otherSupplier = Supplier::create(['name' => 'Supplier Lain', 'is_active' => true]);
        $sameNumber = $this->payload('INV-100');
        $sameNumber['supplier_id'] = $otherSupplier->id;
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $sameNumber)->assertRedirect();
        $this->assertDatabaseCount('purchase_invoices', 2);
    }

    public function test_invoice_total_components_and_discount_reconcile_to_supplier_document(): void
    {
        $payload = $this->payload('INV-COMPONENTS');
        $payload['document_total'] = '120000.00';
        $payload['discount_total'] = '5000.00';

        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $payload)->assertRedirect();
        $invoice = PurchaseInvoice::firstOrFail();

        $this->assertSame('5000.00', $invoice->discount_total);
        $this->assertSame(120000.0, $invoice->calculated_total);
        $this->assertSame(0.0, $invoice->total_variance);
        $this->actingAs($this->finance)->get(route('purchase-invoices.show', $invoice))->assertOk()->assertSee('Subtotal item')->assertSee('Diskon');
    }

    public function test_invoice_total_difference_requires_purchasing_note_and_finance_acknowledgement(): void
    {
        $payload = $this->payload('INV-VARIANCE');
        $payload['document_total'] = '130000.00';
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $payload)->assertSessionHasErrors('total_variance_note');

        $payload['total_variance_note'] = 'Total dokumen mencakup biaya penanganan supplier yang belum dialokasikan.';
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $payload)->assertRedirect();
        $invoice = PurchaseInvoice::firstOrFail();
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.submit', $invoice))->assertRedirect();
        $this->actingAs($this->finance)->post(route('purchase-invoices.review', $invoice), ['decision' => 'accepted'])->assertSessionHasErrors('review_note');
        $this->actingAs($this->finance)->post(route('purchase-invoices.review', $invoice), [
            'decision' => 'accepted',
            'review_note' => 'Finance memeriksa dokumen dan menerima biaya penanganan yang tertera.',
        ])->assertRedirect();

        $this->assertSame('accepted', $invoice->fresh()->status);
    }

    public function test_discount_is_allocated_to_invoice_item_cost_but_other_fees_remain_separate(): void
    {
        $payload = $this->payload('INV-DISCOUNT-COST');
        $payload['document_total'] = '120000.00';
        $payload['discount_total'] = '5000.00';
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $payload)->assertRedirect();
        $invoice = PurchaseInvoice::firstOrFail();

        $location = Location::create(['code' => 'WH-DISCOUNT', 'name' => 'Gudang Diskon', 'type' => 'warehouse', 'is_active' => true]);
        $receipt = StockIn::create([
            'reference' => 'GRN-DISCOUNT', 'supplier_id' => $this->supplier->id,
            'source' => $this->supplier->name, 'location_id' => $location->id,
            'received_by' => $this->purchasing->id, 'received_at' => today(), 'status' => 'approved',
            'purchase_invoice_id' => $invoice->id,
        ]);
        $item = StockInItem::create(['stock_in_id' => $receipt->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        StockMovement::create([
            'product_id' => $this->product->id, 'location_id' => $location->id,
            'user_id' => $this->purchasing->id, 'type' => 'in', 'quantity' => 2,
            'stock_in_item_id' => $item->id,
        ]);

        $row = app(\App\Services\InventoryValuationService::class)->snapshot()->firstOrFail();
        $this->assertSame(60000.0, $row['unit_cost']);
        $this->assertSame(120000.0, $row['value']);
        $this->assertSame('provisional', $row['valuation_status']);
    }

    public function test_finance_can_record_partial_and_full_payment_but_not_overpay(): void
    {
        $invoice = $this->acceptedInvoice('INV-PAYMENT');
        $base = ['paid_at' => today()->toDateString(), 'method' => 'bank_transfer', 'reference' => 'TRF-001'];

        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.store', $invoice), $base + ['amount' => '50000.00'])->assertRedirect();
        $this->assertSame('50000.00', PurchaseInvoicePayment::firstOrFail()->amount);
        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.store', $invoice), $base + ['amount' => '75000.00'])->assertRedirect();
        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.store', $invoice), $base + ['amount' => '0.01'])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('purchase_invoice_payments', 2);
        $this->actingAs($this->finance)->get(route('purchase-invoices.show', $invoice))->assertOk()->assertSee('Status Pembayaran')->assertSee('Paid');
    }

    public function test_payment_reversal_is_an_audited_counter_entry_and_cannot_be_repeated(): void
    {
        $invoice = $this->acceptedInvoice('INV-REVERSAL');
        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.store', $invoice), [
            'paid_at' => today()->toDateString(), 'amount' => '50000.00', 'method' => 'bank_transfer', 'reference' => 'TRF-REV-001',
        ])->assertRedirect();
        $payment = PurchaseInvoicePayment::firstOrFail();

        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.reverse', [$invoice, $payment]), ['reversal_reason' => 'Transfer dibatalkan oleh bank'])->assertRedirect();
        $reversal = PurchaseInvoicePayment::where('entry_type', 'reversal')->firstOrFail();
        $this->assertSame($payment->id, $reversal->reversal_of_id);
        $this->assertSame('50000.00', $reversal->amount);
        $this->assertSame('Transfer dibatalkan oleh bank', $reversal->reversal_reason);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'purchase_invoice.payment_reversed', 'subject_id' => $invoice->id]);

        $this->actingAs($this->finance)->post(route('purchase-invoices.payments.reverse', [$invoice, $payment]), ['reversal_reason' => 'Reversal duplikat'])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('purchase_invoice_payments', 2);
        $this->actingAs($this->finance)->get(route('purchase-invoices.show', $invoice))->assertOk()->assertSee('Unpaid')->assertSee('REVERSAL');
    }

    public function test_only_finance_can_review_submitted_invoice_and_decision_is_audited(): void
    {
        $invoice = $this->createInvoice();
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.submit', $invoice))->assertRedirect();
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.review', $invoice), ['decision' => 'accepted'])->assertForbidden();
        $this->actingAs($this->finance)->post(route('purchase-invoices.review', $invoice), ['decision' => 'revision_required', 'review_category' => 'price', 'review_note' => 'Cek ulang total'])->assertRedirect();

        $this->assertSame('revision_required', $invoice->fresh()->status);
        $this->assertSame($this->finance->id, $invoice->fresh()->reviewed_by);
        $this->assertDatabaseHas('administration_audit_logs', ['action' => 'purchase_invoice.revision_required', 'subject_id' => $invoice->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_revision_captures_before_and_after_item_prices_in_audit_history(): void
    {
        $invoice = $this->createInvoice();
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.submit', $invoice));
        $this->actingAs($this->finance)->post(route('purchase-invoices.review', $invoice), ['decision' => 'revision_required', 'review_category' => 'price', 'review_note' => 'Harga dikoreksi']);
        $this->actingAs($this->purchasing)->get(route('purchase-invoices.edit', $invoice))->assertOk()->assertSee('Ubah Draft Faktur');
        $payload = $this->payload($invoice->invoice_number);
        unset($payload['document']);
        $payload['document_total'] = '130000.00';
        $payload['items'][0]['unit_price'] = '65000.00';
        $this->actingAs($this->purchasing)->put(route('purchase-invoices.update', $invoice), $payload)->assertRedirect(route('purchase-invoices.show', $invoice));

        $log = AdministrationAuditLog::where('action', 'purchase_invoice.updated')->firstOrFail();
        $this->assertSame('62500.00', $log->changes['before']['items'][0]['unit_price']);
        $this->assertSame('65000.00', $log->changes['after']['items'][0]['unit_price']);
        $this->actingAs($this->finance)->get(route('purchase-invoices.show', $invoice))->assertOk()->assertSee('Riwayat perubahan');
    }

    public function test_invoice_document_is_private_and_roles_without_invoice_permission_cannot_download_it(): void
    {
        $invoice = $this->createInvoice();
        $staff = $this->userWithRole('staff_gudang');
        $this->actingAs($staff)->get(route('purchase-invoices.document', $invoice))->assertForbidden();
        $this->actingAs($this->finance)->get(route('purchase-invoices.document', $invoice))->assertOk();
        $this->actingAs($staff)->get(route('purchase-invoices.show', $invoice))->assertForbidden();
    }

    public function test_invoice_detail_exposes_quantity_difference_from_purchase_order_and_receipt(): void
    {
        $location = Location::create(['code' => 'WH-PO', 'name' => 'Gudang PO', 'type' => 'warehouse', 'is_active' => true]);
        $request = PurchaseRequest::create(['reference' => 'PR-PO-TEST', 'requested_by' => $this->purchasing->id, 'status' => 'ordered']);
        $order = PurchaseOrder::create(['reference' => 'PO-TEST', 'purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'location_id' => $location->id, 'created_by' => $this->purchasing->id, 'status' => 'received']);
        $order->items()->create(['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 50000]);

        $payload = $this->payload('INV-DELTA');
        $payload['purchase_order_id'] = $order->id;
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $payload)->assertRedirect();
        $invoice = PurchaseInvoice::firstOrFail();
        $receipt = StockIn::create(['reference' => 'GRN-TEST', 'supplier_id' => $this->supplier->id, 'purchase_order_id' => $order->id, 'source' => $this->supplier->name, 'location_id' => $location->id, 'received_by' => $this->purchasing->id, 'received_at' => today(), 'status' => 'approved']);
        StockInItem::create(['stock_in_id' => $receipt->id, 'product_id' => $this->product->id, 'quantity' => 4]);

        $this->actingAs($this->finance)->get(route('purchase-invoices.show', $invoice))->assertOk()
            ->assertSee('Selisih qty (Invoice - PO):')->assertSee('(Invoice - Receipt):')->assertSee('Harga berbeda dari PO');
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_finance_valuation_shows_ledger_quantity_without_claiming_final_money_value(): void
    {
        $location = Location::create(['code' => 'WH-UJI', 'name' => 'Gudang Uji', 'type' => 'warehouse', 'is_active' => true]);
        StockMovement::create(['product_id' => $this->product->id, 'location_id' => $location->id, 'user_id' => $this->finance->id, 'type' => 'in', 'quantity' => 2]);
        $this->actingAs($this->finance)->get(route('finance.valuation'))->assertOk()->assertSee('nilai dengan harga tersedia')->assertSee('Produk Uji')->assertSee('2,000');

        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('permissions', ['name' => 'invoice.review']);
    }

    private function createInvoice(?string $number = null): PurchaseInvoice
    {
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.store'), $this->payload($number ?? 'INV-'.uniqid()))->assertRedirect();

        return PurchaseInvoice::latest('id')->firstOrFail();
    }

    private function acceptedInvoice(string $number): PurchaseInvoice
    {
        $invoice = $this->createInvoice($number);
        $this->actingAs($this->purchasing)->post(route('purchase-invoices.submit', $invoice))->assertRedirect();
        $this->actingAs($this->finance)->post(route('purchase-invoices.review', $invoice), ['decision' => 'accepted'])->assertRedirect();

        return $invoice->fresh();
    }

    private function payload(string $number): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'invoice_number' => $number,
            'invoice_date' => today()->toDateString(),
            'document_total' => '125000.00',
            'document' => UploadedFile::fake()->create('faktur.pdf', 80, 'application/pdf'),
            'items' => [['product_id' => $this->product->id, 'quantity' => '2.000', 'unit_price' => '62500.00']],
        ];
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user;
    }
}
