<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoicePayment;
use App\Models\StockIn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function create(array $data, UploadedFile $document): PurchaseInvoice
    {
        $this->validateDocumentTotal($data);
        $path = $document->store('purchase-invoices', 'local');
        try {
            return DB::transaction(function () use ($data, $path, $document) {
                $this->assertOrderSupplier($data['purchase_order_id'] ?? null, (int) $data['supplier_id']);
                $invoice = PurchaseInvoice::create([
                    'supplier_id' => $data['supplier_id'], 'purchase_order_id' => $data['purchase_order_id'] ?? null,
                    'invoice_number' => trim($data['invoice_number']), 'invoice_number_key' => mb_strtolower(trim($data['invoice_number'])), 'invoice_date' => $data['invoice_date'],
                    'document_total' => $data['document_total'], 'document_path' => $path,
                    'discount_total' => $data['discount_total'] ?? 0, 'tax_total' => $data['tax_total'] ?? 0,
                    'shipping_total' => $data['shipping_total'] ?? 0, 'other_fee_total' => $data['other_fee_total'] ?? 0,
                    'total_variance_note' => $data['total_variance_note'] ?? null,
                    'document_name' => $document->getClientOriginalName(), 'status' => 'draft', 'created_by' => auth()->id(),
                ]);
                $this->syncItems($invoice, $data['items']);
                $receiptIds = collect();
                if ($invoice->purchase_order_id) {
                    $receiptIds = StockIn::query()
                        ->where('purchase_order_id', $invoice->purchase_order_id)
                        ->whereNull('purchase_invoice_id')
                        ->where('status', 'approved')
                        ->lockForUpdate()
                        ->pluck('id');
                    if ($receiptIds->isNotEmpty()) {
                        StockIn::whereIn('id', $receiptIds)->update(['purchase_invoice_id' => $invoice->id]);
                        $this->auditLogger->record('purchase_invoice.receipts_linked', $invoice, [
                            'stock_in_ids' => $receiptIds->all(),
                            'linked_automatically' => true,
                        ]);
                    }
                }
                $this->auditLogger->record('purchase_invoice.created', $invoice, [
                    'supplier_id' => $invoice->supplier_id, 'invoice_number' => $invoice->invoice_number,
                    'document_total' => $invoice->document_total, 'amount_components' => $this->amountComponents($invoice), 'item_count' => count($data['items']),
                    'linked_stock_in_ids' => $receiptIds->all(),
                ]);

                return $invoice;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function updateDraft(PurchaseInvoice $invoice, array $data, ?UploadedFile $document = null): PurchaseInvoice
    {
        $this->validateDocumentTotal($data);
        $oldPath = $invoice->document_path;
        $newPath = $document?->store('purchase-invoices', 'local');
        try {
            $updated = DB::transaction(function () use ($invoice, $data, $document, $newPath) {
                $invoice = PurchaseInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if (! in_array($invoice->status, ['draft', 'revision_required'], true)) {
                    throw ValidationException::withMessages(['invoice' => 'Faktur yang sudah dikirim tidak dapat diubah sebelum Finance meminta revisi.']);
                }
                $this->assertOrderSupplier($data['purchase_order_id'] ?? null, (int) $data['supplier_id']);
                $before = [
                    'supplier_id' => $invoice->supplier_id, 'invoice_number' => $invoice->invoice_number,
                    'document_total' => $invoice->document_total, 'amount_components' => $this->amountComponents($invoice),
                    'total_variance_note' => $invoice->total_variance_note,
                ];
                $before['items'] = $invoice->items()->orderBy('id')->get(['product_id', 'quantity', 'unit_label', 'unit_price'])->toArray();
                $before['document_name'] = $invoice->document_name;
                $invoice->update([
                    'supplier_id' => $data['supplier_id'], 'purchase_order_id' => $data['purchase_order_id'] ?? null,
                    'invoice_number' => trim($data['invoice_number']), 'invoice_number_key' => mb_strtolower(trim($data['invoice_number'])), 'invoice_date' => $data['invoice_date'],
                    'document_total' => $data['document_total'],
                    'discount_total' => $data['discount_total'] ?? 0, 'tax_total' => $data['tax_total'] ?? 0,
                    'shipping_total' => $data['shipping_total'] ?? 0, 'other_fee_total' => $data['other_fee_total'] ?? 0,
                    'total_variance_note' => $data['total_variance_note'] ?? null,
                    ...($document ? ['document_path' => $newPath, 'document_name' => $document->getClientOriginalName()] : []),
                    'review_note' => null,
                    'review_category' => null,
                ]);
                $invoice->items()->delete();
                $this->syncItems($invoice, $data['items']);
                $after = [
                    'supplier_id' => $invoice->supplier_id, 'invoice_number' => $invoice->invoice_number,
                    'document_total' => $invoice->document_total, 'amount_components' => $this->amountComponents($invoice),
                    'total_variance_note' => $invoice->total_variance_note,
                    'items' => $invoice->items()->orderBy('id')->get(['product_id', 'quantity', 'unit_label', 'unit_price'])->toArray(),
                    'document_name' => $invoice->document_name,
                ];
                $this->auditLogger->record('purchase_invoice.updated', $invoice, ['before' => $before, 'after' => $after]);

                return $invoice;
            });
            if ($document) {
                Storage::disk('local')->delete($oldPath);
            }

            return $updated;
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
    }

    public function submit(PurchaseInvoice $invoice): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = PurchaseInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! in_array($invoice->status, ['draft', 'revision_required'], true) || ! $invoice->items()->exists()) {
                throw ValidationException::withMessages(['invoice' => 'Hanya faktur draft/revisi dengan detail item yang dapat dikirim ke Finance.']);
            }
            $invoice->update(['status' => 'submitted', 'submitted_at' => now(), 'reviewed_by' => null, 'reviewed_at' => null]);
            $this->auditLogger->record('purchase_invoice.submitted', $invoice, ['status' => 'submitted']);

            return $invoice;
        });
    }

    public function review(PurchaseInvoice $invoice, string $decision, string $note, ?string $category = null): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $decision, $note, $category) {
            $invoice = PurchaseInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== 'submitted' || ! in_array($decision, ['accepted', 'rejected', 'revision_required'], true)) {
                throw ValidationException::withMessages(['invoice' => 'Faktur ini tidak berada dalam antrean Finance.']);
            }
            if (in_array($decision, ['rejected', 'revision_required'], true)
                && ($category === null || trim($note) === '')) {
                throw ValidationException::withMessages(['review_note' => 'Kategori dan alasan wajib untuk penolakan atau permintaan revisi.']);
            }
            if ($decision === 'accepted' && abs($invoice->total_variance) > 0.01 && trim($note) === '') {
                throw ValidationException::withMessages(['review_note' => 'Finance perlu mencatat persetujuan atas selisih total faktur dengan komponennya.']);
            }
            $previous = $invoice->status;
            $invoice->update(['status' => $decision, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $note, 'review_category' => $category]);
            $this->auditLogger->record('purchase_invoice.'.$decision, $invoice, ['status_before' => $previous, 'status_after' => $decision, 'review_category' => $category, 'review_note' => $note]);

            return $invoice;
        });
    }

    public function recordPayment(PurchaseInvoice $invoice, array $data): PurchaseInvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status !== 'accepted') {
                throw ValidationException::withMessages([
                    'invoice' => 'Pembayaran hanya dapat dicatat setelah Finance menerima invoice.',
                ]);
            }
            $paidCents = $this->netPaidCents($invoice);
            $amountCents = (int) round((float) $data['amount'] * 100);
            $totalCents = (int) round((float) $invoice->document_total * 100);
            if ($amountCents <= 0 || $paidCents + $amountCents > $totalCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah pembayaran harus positif dan tidak boleh melebihi sisa tagihan.',
                ]);
            }

            $payment = $invoice->payments()->create($data + ['entry_type' => 'payment', 'recorded_by' => auth()->id()]);
            $this->auditLogger->record('purchase_invoice.payment_recorded', $invoice, [
                'payment_id' => $payment->id,
                'paid_at' => $payment->paid_at->toDateString(),
                'amount' => $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'paid_total_before' => $paidCents / 100,
                'paid_total_after' => ($paidCents + $amountCents) / 100,
            ]);

            return $payment;
        });
    }

    public function reversePayment(PurchaseInvoice $invoice, PurchaseInvoicePayment $payment, string $reason): PurchaseInvoicePayment
    {
        return DB::transaction(function () use ($invoice, $payment, $reason) {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = PurchaseInvoicePayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== 'accepted'
                || (int) $payment->purchase_invoice_id !== (int) $invoice->id
                || $payment->entry_type !== 'payment') {
                throw ValidationException::withMessages(['payment' => 'Hanya pembayaran asli pada invoice ini yang dapat dibalik.']);
            }
            if ($payment->reversal()->exists()) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah memiliki reversal.']);
            }

            $reversal = $invoice->payments()->create([
                'entry_type' => 'reversal',
                'reversal_of_id' => $payment->id,
                'paid_at' => today(),
                'amount' => $payment->amount,
                'method' => $payment->method,
                'reference' => 'Reversal pembayaran #'.$payment->id,
                'notes' => 'Reversal dari pembayaran #'.$payment->id,
                'reversal_reason' => trim($reason),
                'recorded_by' => auth()->id(),
            ]);

            $this->auditLogger->record('purchase_invoice.payment_reversed', $invoice, [
                'payment_id' => $payment->id,
                'reversal_id' => $reversal->id,
                'amount' => $reversal->amount,
                'reason' => $reversal->reversal_reason,
                'paid_total_after' => $this->netPaidCents($invoice) / 100,
            ]);

            return $reversal;
        });
    }

    public function linkExistingReceipts(PurchaseInvoice $invoice, array $stockInIds): int
    {
        return DB::transaction(function () use ($invoice, $stockInIds) {
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! $invoice->purchase_order_id) {
                throw ValidationException::withMessages(['stock_in_ids' => 'Invoice perlu ditautkan ke PO sebelum receipt dapat dipilih.']);
            }

            $receipts = StockIn::query()->whereIn('id', $stockInIds)->lockForUpdate()->get();
            if ($receipts->count() !== count(array_unique($stockInIds))) {
                throw ValidationException::withMessages(['stock_in_ids' => 'Satu atau lebih receipt tidak ditemukan.']);
            }
            foreach ($receipts as $receipt) {
                if ((int) $receipt->purchase_order_id !== (int) $invoice->purchase_order_id
                    || (int) $receipt->supplier_id !== (int) $invoice->supplier_id
                    || $receipt->status !== 'approved'
                    || ($receipt->purchase_invoice_id && (int) $receipt->purchase_invoice_id !== (int) $invoice->id)) {
                    throw ValidationException::withMessages(['stock_in_ids' => 'Receipt harus sudah disetujui, berasal dari PO dan supplier invoice ini, serta belum tertaut ke invoice lain.']);
                }
            }

            foreach ($receipts as $receipt) {
                if (! $receipt->purchase_invoice_id) {
                    $receipt->update(['purchase_invoice_id' => $invoice->id]);
                }
            }
            $this->auditLogger->record('purchase_invoice.receipts_linked', $invoice, [
                'stock_in_ids' => $receipts->pluck('id')->all(),
            ]);

            return $receipts->count();
        });
    }

    private function assertOrderSupplier(?int $orderId, int $supplierId): void
    {
        if (! $orderId) {
            return;
        }
        $order = PurchaseOrder::with('items')->findOrFail($orderId);
        if ((int) $order->supplier_id !== $supplierId) {
            throw ValidationException::withMessages(['purchase_order_id' => 'Supplier faktur harus sama dengan supplier pada PO.']);
        }
    }

    private function validateDocumentTotal(array $data): void
    {
        $itemsSubtotal = collect($data['items'])->sum(fn (array $item) => round((float) $item['quantity'] * (float) $item['unit_price'], 2));
        $discount = (float) ($data['discount_total'] ?? 0);
        $calculatedTotal = round($itemsSubtotal - $discount + (float) ($data['tax_total'] ?? 0)
            + (float) ($data['shipping_total'] ?? 0) + (float) ($data['other_fee_total'] ?? 0), 2);
        if ($discount > $itemsSubtotal) {
            throw ValidationException::withMessages(['discount_total' => 'Diskon tidak boleh melebihi subtotal item.']);
        }
        if ($calculatedTotal < 0) {
            throw ValidationException::withMessages(['document_total' => 'Total hasil komponen faktur tidak boleh negatif.']);
        }
        if (abs(round((float) $data['document_total'] - $calculatedTotal, 2)) > 0.01
            && trim((string) ($data['total_variance_note'] ?? '')) === '') {
            throw ValidationException::withMessages(['total_variance_note' => 'Catat alasan selisih antara total dokumen dan subtotal/diskon/pajak/biaya.']);
        }
    }

    private function amountComponents(PurchaseInvoice $invoice): array
    {
        return [
            'discount_total' => $invoice->discount_total,
            'tax_total' => $invoice->tax_total,
            'shipping_total' => $invoice->shipping_total,
            'other_fee_total' => $invoice->other_fee_total,
            'total_variance_note' => $invoice->total_variance_note,
        ];
    }

    private function netPaidCents(PurchaseInvoice $invoice): int
    {
        return (int) round((float) $invoice->payments()->get()->sum(fn (PurchaseInvoicePayment $payment) =>
            (float) $payment->amount * ($payment->entry_type === 'reversal' ? -1 : 1)) * 100);
    }

    private function syncItems(PurchaseInvoice $invoice, array $items): void
    {
        $order = $invoice->purchase_order_id ? PurchaseOrder::with('items')->findOrFail($invoice->purchase_order_id) : null;
        foreach ($items as $line) {
            if ($order && ! $order->items->contains('product_id', (int) $line['product_id'])) {
                throw ValidationException::withMessages(['items' => 'Item faktur harus tersedia pada PO yang dipilih.']);
            }
            $product = Product::with('unit')->findOrFail($line['product_id']);
            $invoice->items()->create([...$line, 'unit_label' => $product->unit->symbol]);
        }
    }
}
