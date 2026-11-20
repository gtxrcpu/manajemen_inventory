<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\Product;
use App\Models\Location;
use App\Models\StockIn;
use App\Models\ProductLocationStockThreshold;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
        private readonly StockInService $stockInService,
        private readonly PurchaseOrderArrivalAlertService $arrivalAlerts,
    ) {}

    public function createRequest(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data) {
            $items = $this->prepareRequestItems($data['items'], (int) $data['location_id']);
            $request = PurchaseRequest::create([
                'reference' => $data['reference'], 'requested_by' => auth()->id(),
                'location_id' => $data['location_id'], 'priority' => $data['priority'],
                'justification' => trim($data['justification']),
                'recommended_supplier_id' => $data['recommended_supplier_id'] ?? null,
                'estimated_total' => collect($items)->sum('estimated_subtotal'),
                'required_date' => $data['required_date'] ?? null, 'notes' => $data['notes'] ?? null,
                'status' => 'pending_approval',
            ]);
            $request->items()->createMany($items);
            $this->auditLogger->record('purchasing.request_submitted', $request, [
                'reference' => $request->reference,
                'item_count' => count($data['items']),
                'status' => 'pending_approval',
            ]);
            return $request;
        });
    }

    public function updateRequest(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($purchaseRequest, $data) {
            $request = PurchaseRequest::query()->whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending_approval' || $request->purchaseOrder()->exists()) {
                throw ValidationException::withMessages(['purchase_request' => 'PR hanya dapat diedit selama masih menunggu persetujuan.']);
            }

            $items = $this->prepareRequestItems($data['items'], (int) $data['location_id']);
            $request->update([
                'location_id' => $data['location_id'], 'priority' => $data['priority'],
                'justification' => trim($data['justification']),
                'recommended_supplier_id' => $data['recommended_supplier_id'] ?? null,
                'estimated_total' => collect($items)->sum('estimated_subtotal'),
                'required_date' => $data['required_date'] ?? null, 'notes' => $data['notes'] ?? null,
            ]);
            $request->items()->delete();
            $request->items()->createMany($items);
            $this->auditLogger->record('purchasing.request_updated', $request, [
                'reference' => $request->reference,
                'location_id' => $request->location_id,
                'priority' => $request->priority,
                'estimated_total' => $request->estimated_total,
                'item_count' => count($items),
                'status' => $request->status,
            ]);

            return $request->fresh(['requester', 'location', 'recommendedSupplier', 'items.product.unit']);
        });
    }

    private function prepareRequestItems(array $items, int $locationId): array
    {
        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $balances = DB::table('stock_movements')->where('location_id', $locationId)->whereIn('product_id', $productIds)
            ->select('product_id')->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as balance")
            ->groupBy('product_id')->pluck('balance', 'product_id');

        $prepared = collect($items)->map(function (array $item) use ($balances) {
            $quantity = round((float) $item['quantity'], 3);
            $unitPrice = round((float) $item['estimated_unit_price'], 2);
            $subtotal = round($quantity * $unitPrice, 2);
            return [
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'stock_snapshot' => round((float) ($balances[$item['product_id']] ?? 0), 3),
                'estimated_unit_price' => $unitPrice,
                'estimated_subtotal' => $subtotal,
                'notes' => isset($item['notes']) && trim($item['notes']) !== '' ? trim($item['notes']) : null,
            ];
        });

        $maximumAmount = 99999999999999.99; // decimal(16, 2)
        if ($prepared->contains(fn (array $item) => $item['estimated_subtotal'] > $maximumAmount)
            || $prepared->sum('estimated_subtotal') > $maximumAmount) {
            throw ValidationException::withMessages(['items' => 'Total estimasi melewati batas nilai yang dapat disimpan. Periksa kembali jumlah dan harga barang.']);
        }

        return $prepared->all();
    }

    public function requestItemEstimate(Product $product, Location $location): array
    {
        $stock = (float) DB::table('stock_movements')->where('location_id', $location->id)->where('product_id', $product->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as balance")->value('balance');
        $onHold = (float) DB::table('stock_disposal_items as items')
            ->join('stock_disposals as disposals', 'disposals.id', '=', 'items.stock_disposal_id')
            ->where('disposals.status', 'pending_approval')
            ->where('disposals.location_id', $location->id)
            ->where('items.product_id', $product->id)
            ->sum('items.quantity');
        $minimum = ProductLocationStockThreshold::query()->where('product_id', $product->id)->where('location_id', $location->id)->value('minimum_quantity');

        $lastInvoicePrice = DB::table('purchase_invoice_items as items')
            ->join('purchase_invoices as invoices', 'invoices.id', '=', 'items.purchase_invoice_id')
            ->where('items.product_id', $product->id)->where('invoices.status', 'accepted')
            ->orderByDesc('invoices.invoice_date')->orderByDesc('invoices.id')->value('items.unit_price');
        $lastPurchasePrice = $lastInvoicePrice ?? DB::table('purchase_order_items as items')
            ->join('purchase_orders as orders', 'orders.id', '=', 'items.purchase_order_id')
            ->where('items.product_id', $product->id)->whereNotNull('items.unit_price')
            ->orderByDesc('orders.created_at')->orderByDesc('orders.id')->value('items.unit_price');

        return [
            'stock_snapshot' => round($stock, 3),
            'available_stock' => round(max(0, $stock - $onHold), 3),
            'minimum_stock' => $minimum !== null ? (float) $minimum : null,
            'below_minimum' => $minimum !== null && $stock < (float) $minimum,
            'estimated_unit_price' => $lastPurchasePrice !== null ? (float) $lastPurchasePrice : null,
        ];
    }

    public function reviewRequest(PurchaseRequest $purchaseRequest, string $decision, ?string $note = null): PurchaseRequest
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException('Keputusan PR tidak valid.');
        }

        return DB::transaction(function () use ($purchaseRequest, $decision, $note) {
            $request = PurchaseRequest::query()->whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending_approval') {
                throw ValidationException::withMessages(['purchase_request' => 'PR ini sudah diputuskan. Muat ulang halaman untuk melihat status terbaru.']);
            }
            if ((int) $request->requested_by === (int) auth()->id()) {
                throw ValidationException::withMessages(['purchase_request' => 'Pembuat PR tidak dapat menyetujui atau menolak PR miliknya sendiri.']);
            }

            $request->update([
                'status' => $decision,
                'reviewed_by' => auth()->id(),
                'approved_by' => $decision === 'approved' ? auth()->id() : null,
                'rejected_by' => $decision === 'rejected' ? auth()->id() : null,
                'reviewed_at' => now(),
                'review_note' => filled($note) ? trim($note) : null,
            ]);

            $this->auditLogger->record('purchasing.request_'.$decision, $request, [
                'reference' => $request->reference,
                'status' => $decision,
                'review_note' => $request->review_note,
                'reviewed_by' => auth()->id(),
            ]);

            return $request->fresh(['requester', 'reviewer', 'items']);
        });
    }

    public function createOrder(?PurchaseRequest $request, array $data): PurchaseOrder
    {
        $order = DB::transaction(function () use ($request, $data) {
            if ($request) {
                $request = PurchaseRequest::with('items')->whereKey($request->id)->lockForUpdate()->firstOrFail();
                if ($request->status !== 'approved' || $request->purchaseOrder()->where('status', '!=', 'cancelled')->exists()) {
                    throw ValidationException::withMessages(['purchase_request_id' => 'PR harus disetujui Management dan belum memiliki PO.']);
                }
            }
            $defaultLines = $request?->items->map(fn ($item) => [
                'product_id' => $item->product_id, 'description' => $item->notes, 'quantity' => $item->quantity,
                'unit_price' => $item->estimated_unit_price ?? 0, 'discount_amount' => 0,
            ])->keyBy('product_id')->all() ?? [];
            $lines = $data['items'] ?? array_values($defaultLines);

            $subtotal = 0;
            $preparedLines = [];
            foreach ($lines as $line) {
                $qty = round((float) $line['quantity'], 3);
                $price = round((float) $line['unit_price'], 2);
                $discount = round((float) ($line['discount_amount'] ?? 0), 2);
                $lineSubtotal = round($qty * $price - $discount, 2);
                if ($lineSubtotal < 0) throw ValidationException::withMessages(['items' => 'Diskon per barang tidak boleh melebihi nilai barang.']);
                if ($lineSubtotal > 99999999999999.99) throw ValidationException::withMessages(['items' => 'Subtotal salah satu barang melewati batas nilai yang dapat disimpan.']);
                $subtotal += $lineSubtotal;
                $preparedLines[] = [...$line, 'quantity' => $qty, 'qty_ordered' => $qty, 'qty_received' => 0, 'unit_price' => $price, 'discount_amount' => $discount, 'subtotal' => $lineSubtotal];
            }
            $taxRate = round((float) ($data['tax_rate'] ?? 0), 2);
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $shippingCost = round((float) ($data['shipping_cost'] ?? 0), 2);
            $otherCost = round((float) ($data['other_cost'] ?? 0), 2);
            if ($subtotal + $taxAmount + $shippingCost + $otherCost > 99999999999999.99) throw ValidationException::withMessages(['items' => 'Grand total PO melewati batas nilai yang dapat disimpan.']);
            $poNumber = $this->nextPurchaseOrderNumber();
            $order = PurchaseOrder::create([
                'reference' => $poNumber, 'po_number' => $poNumber, 'purchase_request_id' => $request?->id,
                'supplier_id' => $data['supplier_id'], 'location_id' => $data['location_id'], 'destination_location_id' => $data['location_id'],
                'created_by' => auth()->id(), 'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_delivery' => $data['expected_delivery_date'] ?? null, 'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? 'net_14', 'delivery_terms' => $data['delivery_terms'] ?? null,
                'subtotal' => $subtotal, 'tax_rate' => $taxRate, 'tax_amount' => $taxAmount, 'shipping_cost' => $shippingCost,
                'other_cost' => $otherCost, 'grand_total' => $subtotal + $taxAmount + $shippingCost + $otherCost,
                'notes' => $data['notes'] ?? null, 'status' => 'draft',
            ]);
            $order->items()->createMany($preparedLines);
            $this->auditLogger->record('purchasing.order_drafted', $order, ['reference' => $order->reference, 'purchase_request_id' => $request?->id, 'grand_total' => $order->grand_total]);
            return $order;
        });

        return $order;
    }

    public function updateDraftOrder(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $data) {
            $order = PurchaseOrder::with('request')->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'draft') throw ValidationException::withMessages(['purchase_order' => 'Hanya PO draft yang dapat diubah.']);
            if ($order->request && $order->request->status !== 'approved') throw ValidationException::withMessages(['purchase_order' => 'PR sumber tidak lagi berstatus disetujui.']);
            $subtotal = 0;
            $lines = [];
            foreach ($data['items'] as $line) {
                $qty = round((float) $line['quantity'], 3); $price = round((float) $line['unit_price'], 2); $discount = round((float) ($line['discount_amount'] ?? 0), 2);
                $lineSubtotal = round($qty * $price - $discount, 2);
                if ($lineSubtotal < 0) throw ValidationException::withMessages(['items' => 'Diskon per barang tidak boleh melebihi nilai barang.']);
                if ($lineSubtotal > 99999999999999.99) throw ValidationException::withMessages(['items' => 'Subtotal salah satu barang melewati batas nilai yang dapat disimpan.']);
                $subtotal += $lineSubtotal;
                $lines[] = [...$line, 'quantity' => $qty, 'qty_ordered' => $qty, 'qty_received' => 0, 'unit_price' => $price, 'discount_amount' => $discount, 'subtotal' => $lineSubtotal];
            }
            $taxRate = round((float) ($data['tax_rate'] ?? 0), 2); $taxAmount = round($subtotal * $taxRate / 100, 2);
            $shipping = round((float) ($data['shipping_cost'] ?? 0), 2); $other = round((float) ($data['other_cost'] ?? 0), 2);
            if ($subtotal + $taxAmount + $shipping + $other > 99999999999999.99) throw ValidationException::withMessages(['items' => 'Grand total PO melewati batas nilai yang dapat disimpan.']);
            $order->update([
                'supplier_id' => $data['supplier_id'], 'location_id' => $data['location_id'], 'destination_location_id' => $data['location_id'],
                'order_date' => $data['order_date'], 'expected_delivery' => $data['expected_delivery_date'] ?? null, 'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'payment_terms' => $data['payment_terms'], 'delivery_terms' => $data['delivery_terms'] ?? null, 'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal, 'tax_rate' => $taxRate, 'tax_amount' => $taxAmount, 'shipping_cost' => $shipping, 'other_cost' => $other,
                'grand_total' => $subtotal + $taxAmount + $shipping + $other,
            ]);
            $order->items()->delete(); $order->items()->createMany($lines);
            $this->auditLogger->record('purchasing.order_draft_updated', $order, ['reference' => $order->reference, 'grand_total' => $order->grand_total]);
            return $order->fresh();
        });
    }

    public function issueOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $order = DB::transaction(function () use ($purchaseOrder) {
            $order = PurchaseOrder::with('request')->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'draft') throw ValidationException::withMessages(['purchase_order' => 'Hanya PO draft yang dapat diterbitkan.']);
            if ($order->request && $order->request->status !== 'approved') throw ValidationException::withMessages(['purchase_order' => 'PR sumber belum disetujui Management.']);
            $order->update(['status' => 'open']);
            $order->request?->update(['status' => 'ordered']);
            $this->auditLogger->record('purchasing.order_issued', $order, ['reference' => $order->reference, 'purchase_request_id' => $order->purchase_request_id, 'grand_total' => $order->grand_total]);
            return $order;
        });
        $this->arrivalAlerts->queue($order);
        return $order;
    }

    public function cancelDraftOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder) {
            $order = PurchaseOrder::with('stockIn')->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if (! in_array($order->status, ['draft', 'open'], true) || $order->stockIn->isNotEmpty()) {
                throw ValidationException::withMessages(['purchase_order' => 'PO hanya dapat dibatalkan sebelum ada penerimaan barang.']);
            }
            $order->update(['status' => 'cancelled']);
            if ($order->request?->status === 'ordered') $order->request->update(['status' => 'approved']);
            $this->auditLogger->record('purchasing.order_cancelled', $order, ['reference' => $order->reference, 'cancelled_by' => auth()->id()]);
            return $order;
        });
    }

    private function nextPurchaseOrderNumber(): string
    {
        $prefix = 'PO-'.now('Asia/Jakarta')->format('Ym').'-';
        $last = (int) DB::table('purchase_orders')->where('po_number', 'like', $prefix.'%')->lockForUpdate()->count();
        do { $candidate = $prefix.str_pad((string) ++$last, 4, '0', STR_PAD_LEFT); }
        while (DB::table('purchase_orders')->where('po_number', $candidate)->exists());
        return $candidate;
    }

    public function syncReceiptProgress(int $purchaseOrderId): void
    {
        $order = PurchaseOrder::with(['items', 'stockIn.items'])->whereKey($purchaseOrderId)->lockForUpdate()->first();
        if (! $order || $order->status === 'cancelled') return;
        $receivedByItem = $order->stockIn
            ->filter(fn (StockIn $receipt) => in_array($receipt->status, ['approved', 'pending_approval'], true))
            ->flatMap(fn (StockIn $receipt) => $receipt->items)
            ->filter(fn ($line) => $line->purchase_order_item_id !== null)
            ->groupBy('purchase_order_item_id')
            ->map(fn ($lines) => (float) $lines->sum('quantity'));
        foreach ($order->items as $item) $item->update(['qty_received' => (float) ($receivedByItem[$item->id] ?? 0)]);
        if ($order->status === 'closed_with_variance') return;
        $complete = $order->items->isNotEmpty() && $order->items->every(fn ($item) => (float) $item->qty_received + 0.0005 >= (float) ($item->qty_ordered ?: $item->quantity));
        $hasReceived = $order->items->contains(fn ($item) => (float) $item->qty_received > 0);
        $order->update(['status' => $complete ? 'completed' : ($hasReceived ? 'partially_received' : 'open'), 'received_at' => $complete ? now() : null]);
    }

    public function recordSupplierFollowUp(PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $order = PurchaseOrder::with(['items', 'stockIn.items'])->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, ['open', 'partially_received', 'completed', 'issued', 'received'], true) || $order->stockIn->isEmpty()) {
                throw ValidationException::withMessages(['follow_up_action' => 'Tindak lanjut supplier hanya dapat dicatat setelah ada penerimaan dan PO belum ditutup.']);
            }

            $receivedByItem = $order->stockIn->flatMap(fn (StockIn $receipt) => $receipt->items)
                ->filter(fn ($line) => $line->purchase_order_item_id !== null)
                ->groupBy('purchase_order_item_id')
                ->map(fn ($lines) => (float) $lines->sum('quantity'));
            $hasRemaining = $order->items->contains(fn ($item) => (float) ($receivedByItem[$item->id] ?? 0) + 0.0005 < (float) ($item->qty_ordered ?: $item->quantity));

            if ($data['action'] === 'close_shortage') {
                if (! in_array($order->status, ['open', 'partially_received', 'issued'], true) || ! $hasRemaining) {
                    throw ValidationException::withMessages(['action' => 'PO hanya dapat ditutup dengan selisih jika masih ada jumlah pesanan yang belum diterima.']);
                }
                $order->status = 'closed_with_variance';
                $order->received_at = now();
            }

            $order->supplier_follow_up_action = $data['action'];
            $order->supplier_follow_up_due_at = $data['due_at'] ?? null;
            $order->supplier_follow_up_note = trim($data['note']);
            $order->supplier_follow_up_by = auth()->id();
            $order->supplier_follow_up_at = now();
            $order->save();

            $this->auditLogger->record('purchasing.supplier_follow_up_recorded', $order, [
                'reference' => $order->reference,
                'action' => $order->supplier_follow_up_action,
                'due_at' => $order->supplier_follow_up_due_at?->toDateString(),
                'note' => $order->supplier_follow_up_note,
                'status_after' => $order->status,
                'recorded_by' => auth()->id(),
            ]);

            return $order;
        });
    }

    public function receiveOrder(PurchaseOrder $order, ?int $invoiceId, string $receivedAt, array $receiptLines, string $idempotencyKey, array $evidencePhotos = []): StockIn
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($order, $invoiceId, $receivedAt, $receiptLines, $idempotencyKey, $evidencePhotos, &$storedPaths) {
            $order = PurchaseOrder::with(['items', 'supplier', 'stockIn.items'])->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previousSubmission = StockIn::where('idempotency_key', $idempotencyKey)->first();
            if ($previousSubmission) {
                if ((int) $previousSubmission->purchase_order_id !== (int) $order->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Kunci submit ini sudah digunakan untuk PO lain. Muat ulang halaman penerimaan.']);
                }

                return $previousSubmission;
            }

            if (! in_array($order->status, ['open', 'partially_received', 'issued'], true)) {
                throw ValidationException::withMessages(['purchase_order' => 'PO ini sudah selesai diterima.']);
            }
            $invoice = $invoiceId ? PurchaseInvoice::with('items')->whereKey($invoiceId)->lockForUpdate()->firstOrFail() : null;
            if ($invoice && ((int) $invoice->purchase_order_id !== (int) $order->id || (int) $invoice->supplier_id !== (int) $order->supplier_id)) {
                throw ValidationException::withMessages(['purchase_invoice_id' => 'Faktur tidak terhubung ke PO dan supplier ini. Muat ulang halaman lalu coba lagi.']);
            }
            $invoiceQuantities = $invoice?->items->keyBy('product_id') ?? collect();
            $expiryTrackedProductIds = Product::query()->whereIn('id', $order->items->pluck('product_id')->unique())
                ->where('requires_expiry_tracking', true)->pluck('id')->map(fn ($id) => (int) $id)->all();

            $receivedByItem = $order->stockIn
                ->filter(fn (StockIn $stockIn) => in_array($stockIn->status, ['approved', 'pending_approval'], true))
                ->flatMap(fn (StockIn $stockIn) => $stockIn->items)
                ->filter(fn ($line) => $line->purchase_order_item_id !== null)
                ->groupBy('purchase_order_item_id')
                ->map(fn ($lines) => (int) round((float) $lines->sum('quantity') * 1000));

            $linesToReceive = [];
            $totalDelivered = 0;
            $allItemsComplete = true;
            foreach ($order->items as $item) {
                $line = $receiptLines[$item->id] ?? [];
                $accepted = (int) round((float) ($line['accepted_quantity'] ?? 0) * 1000);
                $delivered = (int) round((float) ($line['delivered_quantity'] ?? ($line['accepted_quantity'] ?? 0)) * 1000);
                $ordered = (int) round((float) ($item->qty_ordered ?: $item->quantity) * 1000);
                $alreadyAccepted = (int) ($receivedByItem[$item->id] ?? 0);
                $remaining = max(0, $ordered - $alreadyAccepted);
                $varianceNote = trim((string) ($line['variance_note'] ?? ''));

                if ($accepted > $delivered) {
                    throw ValidationException::withMessages(["items.{$item->id}.accepted_quantity" => 'Jumlah layak diterima tidak boleh melebihi jumlah barang yang datang.']);
                }
                if ($accepted > $remaining && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jumlah nyata melebihi sisa PO. Tambahkan catatan selisih sebelum menyimpan.']);
                }
                if ($delivered > $remaining && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Tambahkan catatan jika jumlah datang melebihi sisa PO.']);
                }
                if ($accepted < $delivered && $varianceNote === '') {
                    throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jelaskan barang yang tidak layak diterima.']);
                }
                if ($accepted > 0 && in_array((int) $item->product_id, $expiryTrackedProductIds, true)) {
                    if (blank($line['batch_number'] ?? null)) {
                        throw ValidationException::withMessages(["items.{$item->id}.batch_number" => 'Nomor batch wajib diisi untuk produk yang melacak kedaluwarsa.']);
                    }
                    if (blank($line['expiry_date'] ?? null)) {
                        throw ValidationException::withMessages(["items.{$item->id}.expiry_date" => 'Tanggal kedaluwarsa wajib diisi untuk produk ini.']);
                    }
                }

                $totalDelivered += $delivered;
                if ($alreadyAccepted + $accepted < $ordered) $allItemsComplete = false;
                if ($delivered <= 0) continue;

                $linesToReceive[] = [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'delivered_quantity' => $delivered / 1000,
                    'invoice_quantity' => (float) ($invoiceQuantities->get($item->product_id)?->quantity ?? 0),
                    'quantity' => $accepted / 1000,
                    'variance_note' => $varianceNote ?: null,
                    'batch_number' => $line['batch_number'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'batch_received_at' => $receivedAt,
                ];
            }

            if ($allItemsComplete && $invoice) {
                foreach ($order->items as $item) {
                    $alreadyAccepted = (int) ($receivedByItem[$item->id] ?? 0);
                    $line = $receiptLines[$item->id] ?? [];
                    $accepted = (int) round((float) ($line['accepted_quantity'] ?? 0) * 1000);
                    $invoiceQuantity = (int) round((float) ($invoiceQuantities->get($item->product_id)?->quantity ?? 0) * 1000);
                    $varianceNote = trim((string) ($line['variance_note'] ?? ''));

                    if (abs(($alreadyAccepted + $accepted) - $invoiceQuantity) > 0 && $varianceNote === '') {
                        throw ValidationException::withMessages(["items.{$item->id}.variance_note" => 'Jumlah akhir berbeda dari faktur. Tambahkan catatan selisih sebelum menyimpan.']);
                    }
                }
            }

            if ($totalDelivered <= 0 || $linesToReceive === []) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah barang yang benar-benar datang.']);
            }

            $receiptNumber = $order->stockIn->count() + 1;
            $receipt = $this->stockInService->receive([
                'reference' => 'GRN-PO-'.$order->id.'-'.str_pad((string) $receiptNumber, 3, '0', STR_PAD_LEFT),
                'idempotency_key' => $idempotencyKey,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'purchase_invoice_id' => $invoice?->id,
                'source' => $order->supplier->name,
                'location_id' => $order->location_id,
                'received_at' => $receivedAt,
                'notes' => 'Pemeriksaan barang aktual untuk PO '.$order->reference,
                'items' => $linesToReceive,
            ]);

            foreach ($evidencePhotos as $photo) {
                if (! $photo instanceof UploadedFile) continue;

                $path = $photo->store('stock-in-evidence/'.$receipt->id, 'local');
                $storedPaths[] = $path;
                $receipt->evidences()->create([
                    'file_path' => $path,
                    'original_name' => mb_substr(basename($photo->getClientOriginalName()), 0, 255),
                    'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $photo->getSize() ?: 0,
                ]);
            }

            $this->syncReceiptProgress((int) $order->id);
            $order->refresh();
            $isComplete = $order->status === 'completed';
            $newStatus = $order->status;
            $this->auditLogger->record($isComplete ? 'purchasing.order_received' : 'purchasing.order_partially_received', $order, [
                'stock_in_id' => $receipt->id,
                'status' => $newStatus,
                'delivered_quantity_total' => $totalDelivered / 1000,
                'accepted_quantity_total' => collect($linesToReceive)->sum('quantity'),
                'purchase_invoice_id' => $invoice?->id,
                'purchase_invoice_number' => $invoice?->invoice_number,
                'evidence_photo_count' => count($storedPaths),
                'invoice_link_status' => $invoice ? 'linked' : 'pending_document',
                'invoice_quantities' => collect($linesToReceive)->map(fn ($line) => [
                    'product_id' => $line['product_id'],
                    'invoice_quantity' => $line['invoice_quantity'],
                    'received_quantity' => $line['quantity'],
                ])->all(),
            ]);
            return $receipt;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }
    }
}
