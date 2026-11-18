<section class="rounded-xl border bg-slate-50 p-3 text-sm">
    <h2 class="font-semibold">Rekonsiliasi total faktur</h2>
    <dl class="mt-2 grid grid-cols-2 gap-2 text-xs">
        <dt>Subtotal item</dt><dd class="text-right">Rp {{ number_format($invoice->items_subtotal, 2, ',', '.') }}</dd>
        <dt>Diskon</dt><dd class="text-right">− Rp {{ number_format((float) $invoice->discount_total, 2, ',', '.') }}</dd>
        <dt>Pajak</dt><dd class="text-right">Rp {{ number_format((float) $invoice->tax_total, 2, ',', '.') }}</dd>
        <dt>Ongkir</dt><dd class="text-right">Rp {{ number_format((float) $invoice->shipping_total, 2, ',', '.') }}</dd>
        <dt>Biaya lain</dt><dd class="text-right">Rp {{ number_format((float) $invoice->other_fee_total, 2, ',', '.') }}</dd>
        <dt class="border-t pt-2 font-semibold">Total hasil hitung</dt><dd class="border-t pt-2 text-right font-semibold">Rp {{ number_format($invoice->calculated_total, 2, ',', '.') }}</dd>
        <dt class="font-semibold">Total dokumen</dt><dd class="text-right font-semibold">Rp {{ number_format((float) $invoice->document_total, 2, ',', '.') }}</dd>
    </dl>
    @if(abs($invoice->total_variance) > 0.01)
        <p class="mt-2 rounded-lg bg-amber-100 p-2 text-xs text-amber-900">Selisih total: Rp {{ number_format(abs($invoice->total_variance), 2, ',', '.') }}. {{ $invoice->total_variance_note }}</p>
        @if($invoice->status === 'submitted')
            <p class="mt-2 text-xs text-amber-800">Finance perlu mencatat keputusan pada catatan review sebelum menerima selisih ini.</p>
        @endif
    @endif
    <p class="mt-2 text-[11px] text-gray-500">Pajak dan biaya lain ditampilkan terpisah; belum otomatis masuk biaya perolehan persediaan.</p>
</section>
