<section class="rounded-2xl border {{ $receipt->status === 'rejected' ? 'border-red-200 bg-red-50' : ($receipt->status === 'pending_approval' ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50') }} p-4 space-y-3">
    <div>
        <p class="text-sm font-semibold">Status persetujuan: {{ ['pending_approval' => 'Menunggu PIC outlet', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$receipt->status] ?? 'Tercatat' }}</p>
        @if($receipt->status === 'pending_approval')
            <p class="mt-1 text-xs text-gray-600">Saldo outlet belum bertambah sampai penerimaan ini disetujui.</p>
        @elseif($receipt->decisionMaker)
            <p class="mt-1 text-xs text-gray-600">Keputusan oleh {{ $receipt->decisionMaker->name }} · {{ $receipt->decided_at?->format('d M Y, H:i') }}</p>
        @endif
        @if($receipt->status === 'rejected')
            <p class="mt-2 text-sm"><span class="font-semibold">Kategori:</span> {{ $receipt->rejection_category ? str_replace('_', ' ', $receipt->rejection_category) : 'Belum dikategorikan (data lama)' }}</p>
            <p class="text-sm"><span class="font-semibold">Alasan penolakan:</span> {{ $receipt->rejection_reason }}</p>
            @if(auth()->user()->hasPermissionTo('stock.manage'))
                <p class="mt-1 text-xs text-gray-600">Buat Stock In baru setelah koreksi; catatan transaksi yang ditolak ini tetap tersimpan.</p>
            @endif
        @endif
    </div>

    @if($receipt->status === 'pending_approval' && $canApprove)
        <form method="POST" action="{{ route('inventory.stock-in.approve', $receipt) }}">
            @csrf
            <button class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Setujui penerimaan &amp; tambahkan ke saldo outlet</button>
        </form>
        <form method="POST" action="{{ route('inventory.stock-in.reject', $receipt) }}" class="space-y-2">
            @csrf
            <label class="block text-xs font-semibold">Kategori penolakan wajib dipilih
                <select required name="rejection_category" class="mt-1 w-full rounded-lg border border-red-200 bg-white p-2.5 text-sm"><option value="">Pilih kategori</option><option value="quantity_mismatch">Kuantitas / selisih</option><option value="condition">Kondisi barang</option><option value="document">Dokumen</option><option value="other">Lainnya</option></select>
            </label>
            <label class="block text-xs font-semibold">Alasan penolakan wajib diisi
                <textarea required name="rejection_reason" minlength="3" maxlength="5000" rows="3" class="mt-1 w-full rounded-lg border border-red-200 bg-white p-2.5 text-sm" placeholder="Jelaskan barang atau jumlah yang tidak sesuai"></textarea>
            </label>
            <button class="rounded-xl border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700">Tolak Stock In</button>
        </form>
    @endif
</section>
