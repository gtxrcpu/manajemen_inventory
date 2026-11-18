<div class="space-y-2">
    @forelse($invoice->payments as $payment)
        <article class="border-t py-2 text-xs text-gray-600">
            <p class="font-semibold {{ $payment->entry_type === 'reversal' ? 'text-amber-700' : '' }}">
                {{ $payment->entry_type === 'reversal' ? 'REVERSAL' : 'Pembayaran' }} · {{ $payment->paid_at->format('d M Y') }} · Rp {{ number_format((float) $payment->amount, 2, ',', '.') }}
            </p>
            <p>{{ str_replace('_', ' ', $payment->method) }} · {{ $payment->recorder?->name ?? 'Pengguna dihapus' }}@if($payment->reference) · {{ $payment->reference }}@endif</p>
            @if($payment->entry_type === 'reversal')
                <p>Balik pembayaran #{{ $payment->reversal_of_id }}: {{ $payment->reversal_reason }}</p>
            @elseif($payment->reversal)
                <p class="text-amber-700">Dibalik {{ $payment->reversal->paid_at->format('d M Y') }} · {{ $payment->reversal->reversal_reason }}</p>
            @elseif($canPay && $invoice->status === 'accepted')
                <form method="POST" action="{{ route('purchase-invoices.payments.reverse', [$invoice, $payment]) }}" class="mt-2 flex flex-wrap gap-2">
                    @csrf
                    <input name="reversal_reason" required minlength="3" maxlength="2000" placeholder="Alasan reversal" class="min-w-0 flex-1 rounded border p-2">
                    <button class="rounded border border-amber-300 px-3 py-2 font-semibold text-amber-800">Balik transaksi</button>
                </form>
            @endif
        </article>
    @empty
        <p class="border-t py-2 text-xs text-gray-500">Belum ada pembayaran.</p>
    @endforelse
</div>
