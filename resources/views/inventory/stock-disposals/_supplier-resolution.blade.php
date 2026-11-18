@if($disposal->type === 'supplier_return')
@php($resolvedTotal = (float) $disposal->items->sum(fn ($item) => $item->supplierResolutions->sum('quantity')))
<section class="rounded-xl border border-blue-200 bg-white p-4 sm:p-5">
    <h2 class="font-semibold">Klaim dan penyelesaian supplier</h2>
    <p class="mt-1 text-xs text-gray-500">Invoice {{ $disposal->invoice?->invoice_number ?? '—' }} · retur approved {{ number_format($disposal->items->sum('quantity'), 3, ',', '.') }} · terselesaikan {{ number_format($resolvedTotal, 3, ',', '.') }}</p>
    <div class="mt-3 space-y-3">
        @foreach($disposal->items as $item)
            @php($resolved = (float) $item->supplierResolutions->sum('quantity'))
            <div class="rounded-lg bg-gray-50 p-3">
                <p class="text-sm font-semibold">{{ $item->product->name }} · batch {{ $item->batch?->batch_number ?: 'Tidak diketahui' }}</p>
                <p class="mt-1 text-xs text-gray-600">Retur {{ number_format((float) $item->quantity, 3, ',', '.') }} · belum terselesaikan {{ number_format(max(0, (float) $item->quantity - $resolved), 3, ',', '.') }} {{ $item->product->unit->symbol }}</p>
                @foreach($item->supplierResolutions as $resolution)
                    <p class="mt-2 border-t pt-2 text-xs text-gray-600">
                        {{ match($resolution->resolution_type) { 'credit_note' => 'Credit note', 'refund' => 'Refund', 'replacement' => 'Penggantian barang', 'claim_rejected' => 'Klaim ditolak', default => $resolution->resolution_type } }}
                        · {{ number_format((float) $resolution->quantity, 3, ',', '.') }} {{ $item->product->unit->symbol }}
                        @if($resolution->amount) · Rp{{ number_format((float) $resolution->amount, 2, ',', '.') }} @endif
                        @if($resolution->reference) · {{ $resolution->reference }} @endif
                        @if($resolution->replacementReceipt) · Receipt {{ $resolution->replacementReceipt->reference }} @endif
                        · {{ $resolution->recorder?->name }} · {{ $resolution->resolved_at?->format('d/m/Y') }}
                        @if($resolution->note)<span class="block">{{ $resolution->note }}</span>@endif
                    </p>
                @endforeach
            </div>
        @endforeach
    </div>

    @if($canResolve && $disposal->status === 'approved' && $disposal->items->contains(fn ($item) => (float) $item->quantity - (float) $item->supplierResolutions->sum('quantity') > 0.0005))
        <form method="POST" action="{{ route('inventory.stock-disposals.supplier-resolution', $disposal) }}" class="mt-4 grid gap-3 rounded-lg border p-3 sm:grid-cols-2" data-supplier-resolution-form>
            @csrf
            <label class="text-xs">Item retur<select required name="stock_disposal_item_id" class="mt-1 w-full rounded border p-2">@foreach($disposal->items as $item)@php($resolved = (float) $item->supplierResolutions->sum('quantity'))@if((float) $item->quantity - $resolved > 0.0005)<option value="{{ $item->id }}" data-product-id="{{ $item->product_id }}" data-remaining="{{ (float) $item->quantity - $resolved }}">{{ $item->product->name }} · sisa klaim {{ number_format((float) $item->quantity - $resolved, 3, ',', '.') }}</option>@endif @endforeach</select></label>
            <label class="text-xs">Hasil supplier<select required name="resolution_type" data-resolution-type class="mt-1 w-full rounded border p-2"><option value="credit_note">Credit note</option><option value="refund">Refund</option><option value="replacement">Barang pengganti sudah diterima</option><option value="claim_rejected">Klaim ditolak supplier</option></select></label>
            <label class="text-xs">Jumlah klaim yang ditutup<input required type="number" name="quantity" min="0.001" step="0.001" max="{{ $disposal->items->max(fn ($item) => (float) $item->quantity - (float) $item->supplierResolutions->sum('quantity')) }}" class="mt-1 w-full rounded border p-2"></label>
            <label class="text-xs" data-resolution-amount-field>Nilai credit note / refund (Rp)<input type="number" name="amount" min="0.01" step="0.01" class="mt-1 w-full rounded border p-2"></label>
            <label class="text-xs" data-resolution-reference-field>No. credit note / bukti keputusan<input type="text" name="reference" maxlength="150" class="mt-1 w-full rounded border p-2"></label>
            <label class="text-xs hidden" data-replacement-receipt-field>Receipt barang pengganti<select name="replacement_stock_in_id" class="mt-1 w-full rounded border p-2"><option value="">Pilih receipt approved</option>@foreach($replacementReceipts as $receipt)@foreach($receipt->items as $receiptItem)@if($disposal->items->contains('product_id', (int) $receiptItem->product_id))<option value="{{ $receipt->id }}" data-product-id="{{ $receiptItem->product_id }}">{{ $receipt->reference }} · {{ $receiptItem->product->name }} · {{ number_format((float) $receiptItem->quantity, 3, ',', '.') }}</option>@endif @endforeach @endforeach</select></label>
            <label class="text-xs sm:col-span-2">Catatan / alasan<textarea name="note" rows="2" maxlength="5000" class="mt-1 w-full rounded border p-2"></textarea></label>
            <button class="rounded-lg bg-blue-700 p-2 text-sm font-semibold text-white sm:col-span-2">Catat penyelesaian klaim</button>
        </form>
    @endif
</section>
@endif
