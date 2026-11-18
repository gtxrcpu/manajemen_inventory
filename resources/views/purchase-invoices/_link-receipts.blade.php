@if($canManage && $unlinkedReceipts->isNotEmpty())
    <section class="rounded-2xl border bg-white p-4">
        <h2 class="font-semibold">Tautkan receipt yang belum memiliki invoice</h2>
        <p class="mt-1 text-xs text-secondary">Pilih receipt dari PO yang tercantum di invoice ini. Riwayat penerimaan dan saldo tidak diubah.</p>
        <form method="POST" action="{{ route('purchase-invoices.receipts.link', $invoice) }}" class="mt-3 space-y-2">
            @csrf
            @foreach($unlinkedReceipts as $receipt)
                <label class="flex items-start gap-2 border-t py-2 text-sm">
                    <input type="checkbox" name="stock_in_ids[]" value="{{ $receipt->id }}" class="mt-1">
                    <span><b>{{ $receipt->reference }}</b><span class="block text-xs text-secondary">{{ $receipt->received_at->format('d M Y') }} · {{ $receipt->receiver?->name ?? 'Petugas tidak tersedia' }}</span></span>
                </label>
            @endforeach
            <button class="w-full rounded-lg border p-2 text-sm font-semibold">Tautkan receipt terpilih</button>
        </form>
    </section>
@endif
