@php
    $followUpActionLabels = [
        'awaiting_delivery' => 'Menunggu sisa barang dari supplier',
        'replacement_expected' => 'Menunggu barang pengganti',
        'credit_note_expected' => 'Menunggu credit note',
        'refund_expected' => 'Menunggu refund',
        'close_shortage' => 'Sisa PO ditutup dengan selisih / kompensasi',
    ];
@endphp

<div class="mt-4 rounded-xl border border-amber-200 bg-white p-4">
    @if($order->supplier_follow_up_action)
        <div class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-950">
            <p class="font-semibold">Tindak lanjut terakhir: {{ $followUpActionLabels[$order->supplier_follow_up_action] ?? $order->supplier_follow_up_action }}</p>
            @if($order->supplier_follow_up_due_at)<p class="mt-1 text-xs">Tanggal janji / follow-up: {{ $order->supplier_follow_up_due_at->format('d M Y') }}</p>@endif
            <p class="mt-1 whitespace-pre-line text-xs">{{ $order->supplier_follow_up_note }}</p>
            <p class="mt-1 text-[11px] text-amber-800">Dicatat {{ $order->supplier_follow_up_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB oleh {{ $order->supplierFollowUpBy?->name ?? 'Purchasing' }}.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('purchasing.orders.supplier-follow-up', $order) }}" class="grid gap-3 sm:grid-cols-2" data-supplier-follow-up-form>
        @csrf
        <label class="block text-xs font-semibold text-gray-700">Hasil follow-up supplier
            <select required name="action" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm font-normal">
                <option value="awaiting_delivery" @selected(old('action', $order->supplier_follow_up_action) === 'awaiting_delivery')>Menunggu sisa barang</option>
                <option value="replacement_expected" @selected(old('action', $order->supplier_follow_up_action) === 'replacement_expected')>Menunggu barang pengganti</option>
                <option value="credit_note_expected" @selected(old('action', $order->supplier_follow_up_action) === 'credit_note_expected')>Menunggu credit note</option>
                <option value="refund_expected" @selected(old('action', $order->supplier_follow_up_action) === 'refund_expected')>Menunggu refund</option>
                @if($order->status === 'issued' && $remainingPoQuantity > 0)
                    <option value="close_shortage" @selected(old('action', $order->supplier_follow_up_action) === 'close_shortage')>Selesaikan sisa PO dengan selisih / kompensasi</option>
                @endif
            </select>
        </label>
        <label class="block text-xs font-semibold text-gray-700">Tanggal janji supplier / tanggal tagih ulang
            <input type="date" name="due_at" value="{{ old('due_at', $order->supplier_follow_up_due_at?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm font-normal">
        </label>
        <label class="block text-xs font-semibold text-gray-700 sm:col-span-2">Catatan Purchasing <span class="font-normal text-gray-500">(kontak supplier, nomor klaim, alasan tutup, atau bukti)</span>
            <textarea required minlength="5" maxlength="5000" name="note" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm font-normal" placeholder="Contoh: Supplier menyanggupi mengirim pengganti 2 kg paling lambat 10 Nov; kontak Budi, WA ...">{{ old('note', $order->supplier_follow_up_note) }}</textarea>
        </label>
        <div class="sm:col-span-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] leading-relaxed text-gray-500">Untuk menutup sisa, catat konfirmasi supplier dan kompensasi yang disepakati (misalnya credit note/refund) pada catatan. Penutupan menghentikan penerimaan lanjutan dan tidak mengubah saldo stok.</p>
            <button class="shrink-0 rounded-lg bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white">Simpan tindak lanjut</button>
        </div>
    </form>
</div>
