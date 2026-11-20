<?php

namespace App\Http\Controllers;

use App\Models\AdministrationAuditLog;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Location;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Services\InventoryValuationService;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseInvoiceController extends Controller
{
    private function authorizeInvoiceView(): void
    {
        $user = auth()->user();
        abort_unless($user->hasRole('super_admin') || $user->hasPermissionTo('invoice.view') || $user->hasPermissionTo('invoice.review'), 403);
    }

    private function authorizePurchasing(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.manage'), 403);
    }

    private function authorizeFinance(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.review'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeInvoiceView();
        $query = PurchaseInvoice::with(['supplier', 'order', 'creator'])->withCount('items')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('invoice_number', 'like', '%'.$request->string('q').'%')->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', '%'.$request->string('q').'%')));
        }

        return view('purchase-invoices.index', ['invoices' => $query->paginate(12)->withQueryString()]);
    }

    public function create(Request $request): View
    {
        $this->authorizePurchasing();
        $options = $this->formOptions();
        $selectedOrder = $options['orders']->firstWhere('id', $request->integer('purchase_order_id'));

        return view('purchase-invoices.form', [
            'invoice' => null,
            ...$options,
            'selectedOrderId' => $selectedOrder?->id,
            'selectedSupplierId' => $selectedOrder?->supplier_id,
            'selectedOrder' => $selectedOrder,
            'initialItems' => $selectedOrder?->invoice_prefill_items ?? [],
        ]);
    }

    public function store(Request $request, PurchaseInvoiceService $service): RedirectResponse
    {
        $this->authorizePurchasing();
        $data = $this->validated($request);
        $invoice = $service->create($data, $data['document']);

        return redirect()->route('purchase-invoices.show', $invoice)->with('status', 'Faktur tersimpan sebagai draft. Periksa detail lalu kirim ke Finance.');
    }

    public function edit(PurchaseInvoice $purchaseInvoice): View
    {
        $this->authorizePurchasing();
        abort_unless(in_array($purchaseInvoice->status, ['draft', 'revision_required'], true), 403);

        return view('purchase-invoices.form', ['invoice' => $purchaseInvoice->load('items'), ...$this->formOptions()]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceService $service): RedirectResponse
    {
        $this->authorizePurchasing();
        $data = $this->validated($request, false, $purchaseInvoice);
        $invoice = $service->updateDraft($purchaseInvoice, $data, $data['document'] ?? null);

        return redirect()->route('purchase-invoices.show', $invoice)->with('status', 'Draft faktur diperbarui.');
    }

    public function show(PurchaseInvoice $purchaseInvoice): View
    {
        $this->authorizeInvoiceView();
        $invoice = $purchaseInvoice->load(['supplier', 'order.location', 'order.items.product.unit', 'items.product.unit', 'creator', 'reviewer', 'payments.recorder', 'payments.reversal', 'payments.reversalOf']);
        $receipts = $invoice->purchase_order_id
            ? StockIn::with('items.product')->where('purchase_order_id', $invoice->purchase_order_id)->get()
            : collect();
        $unlinkedReceipts = $invoice->purchase_order_id
            ? StockIn::with('receiver')->where('purchase_order_id', $invoice->purchase_order_id)->whereNull('purchase_invoice_id')->where('status', 'approved')->latest('received_at')->get()
            : collect();
        $comparison = $this->comparison($invoice, $receipts);
        $auditTrail = AdministrationAuditLog::with('actor')->where('subject_type', PurchaseInvoice::class)->where('subject_id', $invoice->id)->latest()->get();
        $canManage = auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.manage');
        $canReview = auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.review');
        $canPay = ($invoice->status === 'accepted')
            && (auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.payment.manage'));

        $paidTotal = (float) $invoice->payments->sum(fn ($payment) => (float) $payment->amount * ($payment->entry_type === 'reversal' ? -1 : 1));
        $outstanding = max(0, (float) $invoice->document_total - $paidTotal);
        $paymentStatus = $paidTotal <= 0 ? 'unpaid' : ($outstanding <= 0.005 ? 'paid' : 'partially_paid');
        $hasTotalVariance = abs($invoice->total_variance) > 0.01;

        return view('purchase-invoices.show', compact('invoice', 'comparison', 'auditTrail', 'canManage', 'canReview', 'canPay', 'paidTotal', 'outstanding', 'paymentStatus', 'unlinkedReceipts', 'hasTotalVariance'));
    }

    public function submit(PurchaseInvoice $purchaseInvoice, PurchaseInvoiceService $service): RedirectResponse
    {
        $this->authorizePurchasing();
        $service->submit($purchaseInvoice);

        return back()->with('status', 'Faktur dikirim ke antrean Finance.');
    }

    public function review(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceService $service): RedirectResponse
    {
        $this->authorizeFinance();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accepted', 'rejected', 'revision_required'])],
            'review_category' => ['required_if:decision,rejected,revision_required', 'nullable', Rule::in(['quantity', 'price', 'supplier_po', 'invalid_document', 'damaged_expired', 'other'])],
            'review_note' => ['required_if:decision,rejected,revision_required', 'nullable', 'string', 'max:2000'],
        ]);
        $service->review($purchaseInvoice, $data['decision'], $data['review_note'] ?? '', $data['review_category'] ?? null);

        return back()->with('status', 'Keputusan Finance tercatat. Status ini tidak mengubah penerimaan atau pembayaran.');
    }

    public function recordPayment(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.payment.manage'), 403);
        $data = $request->validate([
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'method' => ['required', Rule::in(['bank_transfer', 'cash', 'other'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->recordPayment($purchaseInvoice, $data);

        return back()->with('status', 'Pembayaran dicatat. Status pembayaran dihitung terpisah dari review faktur.');
    }

    public function reversePayment(Request $request, PurchaseInvoice $purchaseInvoice, \App\Models\PurchaseInvoicePayment $payment, PurchaseInvoiceService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('invoice.payment.manage'), 403);
        $data = $request->validate(['reversal_reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $service->reversePayment($purchaseInvoice, $payment, $data['reversal_reason']);

        return back()->with('status', 'Reversal pembayaran tercatat sebagai transaksi audit.');
    }

    public function linkReceipts(Request $request, PurchaseInvoice $purchaseInvoice, PurchaseInvoiceService $service): RedirectResponse
    {
        $this->authorizePurchasing();
        $data = $request->validate([
            'stock_in_ids' => ['required', 'array', 'min:1'],
            'stock_in_ids.*' => ['required', 'integer', 'distinct', 'exists:stock_ins,id'],
        ]);
        $count = $service->linkExistingReceipts($purchaseInvoice, array_map('intval', $data['stock_in_ids']));

        return back()->with('status', $count.' receipt berhasil ditautkan ke invoice.');
    }

    public function document(PurchaseInvoice $purchaseInvoice): StreamedResponse
    {
        $user = auth()->user();
        $canInspectWarehouseInvoice = $user->isWarehouseStaff()
            && $purchaseInvoice->order()->whereIn('location_id', $user->assignedWarehouseLocationIds())
                ->whereHas('location', fn ($locations) => $locations->where('type', \App\Models\Location::TYPE_WAREHOUSE))->exists();
        abort_unless($user->hasRole('super_admin') || $user->hasPermissionTo('invoice.view') || $user->hasPermissionTo('invoice.review') || $canInspectWarehouseInvoice, 403);
        abort_unless(Storage::disk('local')->exists($purchaseInvoice->document_path), 404);

        return Storage::disk('local')->download($purchaseInvoice->document_path, $purchaseInvoice->document_name);
    }

    public function valuation(Request $request, InventoryValuationService $service): View
    {
        $this->authorizeValuation();
        $filters = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $rows = $service->snapshot(isset($filters['location_id']) ? (int) $filters['location_id'] : null, isset($filters['product_id']) ? (int) $filters['product_id'] : null);
        return view('purchase-invoices.valuation', [
            'rows' => $rows,
            'totalValue' => $rows->sum('value'),
            'hasIncompleteValue' => $rows->contains(fn ($row) => $row['valuation_status'] === 'incomplete'),
            'hasProvisionalValue' => $rows->contains(fn ($row) => $row['valuation_status'] === 'provisional'),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'sku', 'name']),
            'selectedLocation' => $filters['location_id'] ?? '',
            'selectedProduct' => $filters['product_id'] ?? '',
            'generatedAt' => now(),
        ]);
    }

    public function exportValuation(Request $request, InventoryValuationService $service): StreamedResponse
    {
        $this->authorizeValuation();
        $filters = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $rows = $service->snapshot(isset($filters['location_id']) ? (int) $filters['location_id'] : null, isset($filters['product_id']) ? (int) $filters['product_id'] : null);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SKU', 'Produk', 'Lokasi', 'Saldo', 'Satuan', 'Harga rata-rata terhitung (Rp)', 'Nilai terhitung (Rp)', 'Status valuasi', 'Qty provisional', 'Qty tanpa harga', 'Biaya belum dialokasikan (Rp)', 'Selisih dokumen (Rp)', 'Sumber invoice/receipt', 'Referensi transfer'], ';', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['sku'], $row['product_name'], $row['location_name'], $row['quantity'], $row['unit_symbol'],
                    $row['valuation_status'] === 'incomplete' ? '' : $row['unit_cost'], $row['value'], $row['valuation_status'],
                    $row['provisional_quantity'], $row['unpriced_quantity'], $row['unallocated_cost'], $row['document_variance'],
                    implode(', ', $row['source_invoices'] ?? []), implode(', ', array_unique($row['transfer_references'] ?? [])),
                ], ';', '"', '\\');
            }
            fclose($output);
        }, 'valuasi-persediaan-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeValuation(): void
    {
        $user = auth()->user();
        abort_unless($user->hasRole('super_admin') || $user->hasRole('management') || $user->hasPermissionTo('invoice.review'), 403);
    }

    private function formOptions(): array
    {
        $orders = PurchaseOrder::with([
            'supplier',
            'items.product.unit',
            'stockIn' => fn ($query) => $query->where('status', 'approved')->with('items'),
        ])->whereIn('status', ['open', 'partially_received', 'issued', 'completed', 'received', 'closed_with_variance'])->latest()->get();

        $orders->each(function (PurchaseOrder $order): void {
            $receivedByOrderItem = $order->stockIn
                ->flatMap(fn ($receipt) => $receipt->items)
                ->filter(fn ($line) => $line->purchase_order_item_id !== null)
                ->groupBy('purchase_order_item_id')
                ->map(fn ($lines) => (float) $lines->sum('quantity'));
            $hasConfirmedReceipt = $order->stockIn->isNotEmpty();
            $prefillItems = $order->items
                ->filter(fn ($item) => ! $hasConfirmedReceipt || (float) ($receivedByOrderItem->get($item->id) ?? 0) > 0)
                ->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $hasConfirmedReceipt ? (float) $receivedByOrderItem->get($item->id) : (float) ($item->qty_ordered ?: $item->quantity),
                    'unit_price' => (float) $item->unit_price,
                ])
                ->values()
                ->all();

            $order->setAttribute('has_confirmed_receipt', $hasConfirmedReceipt);
            $order->setAttribute('invoice_prefill_items', $prefillItems);
        });

        return [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'orders' => $orders,
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, bool $documentRequired = true, ?PurchaseInvoice $invoice = null): array
    {
        $request->merge(['invoice_number_key' => mb_strtolower(trim((string) $request->input('invoice_number', '')))]);

        return $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id,deleted_at,NULL'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'invoice_number' => ['required', 'string', 'max:120', Rule::unique('purchase_invoices', 'invoice_number_key')->where('supplier_id', $request->input('supplier_id'))->ignore($invoice?->id)],
            'invoice_date' => ['required', 'date'],
            'document_total' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'discount_total' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'tax_total' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'shipping_total' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'other_fee_total' => ['nullable', 'numeric', 'gte:0', 'max:999999999999.99'],
            'total_variance_note' => ['nullable', 'string', 'max:2000'],
            'document' => [$documentRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id,deleted_at,NULL'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999.999'],
            'items.*.unit_price' => ['required', 'numeric', 'gte:0', 'max:999999999999.99'],
        ]);
    }

    private function comparison(PurchaseInvoice $invoice, Collection $receipts): Collection
    {
        $poItems = $invoice->order?->items->keyBy('product_id') ?? collect();
        $receivedItems = $receipts->flatMap(fn (StockIn $receipt) => $receipt->items)
            ->groupBy('product_id')
            ->map(fn ($lines) => (object) [
                'product' => $lines->first()->product,
                'quantity' => $lines->sum('quantity'),
            ]);
        $invoiceItems = $invoice->items->keyBy('product_id');
        $productIds = $invoiceItems->keys()->merge($poItems->keys())->merge($receivedItems->keys())->unique();

        return $productIds->map(function ($productId) use ($invoiceItems, $poItems, $receivedItems) {
            $line = $invoiceItems->get($productId);
            $po = $poItems->get($productId);
            $received = $receivedItems->get($productId);
            $product = $line?->product ?? $po?->product ?? $received?->product;

            return [
                'product' => $product,
                'invoice_quantity' => $line?->quantity,
                'invoice_unit_price' => $line?->unit_price,
                'po_quantity' => $po?->quantity,
                'po_unit_price' => $po?->unit_price,
                'price_mismatch' => $line && $po?->unit_price !== null
                    && number_format((float) $line->unit_price, 2, '.', '') !== number_format((float) $po->unit_price, 2, '.', ''),
                'invoice_po_quantity_delta' => $line && $po ? round((float) $line->quantity - (float) $po->quantity, 3) : null,
                'invoice_receipt_quantity_delta' => $line && $received ? round((float) $line->quantity - (float) $received->quantity, 3) : null,
                'invoice_po_unit_price_delta' => $line && $po?->unit_price !== null ? round((float) $line->unit_price - (float) $po->unit_price, 2) : null,
                'received_quantity' => $received?->quantity,
                'invoice_status' => $line ? 'Ada pada faktur' : 'Item PO tidak dicantumkan pada faktur',
                'receipt_status' => $received ? 'Tercatat pada Stock In' : ($po ? 'Belum tercatat pada Stock In' : 'Tidak terhubung ke PO'),
            ];
        });
    }
}
