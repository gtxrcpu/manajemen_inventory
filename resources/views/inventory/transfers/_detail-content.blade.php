<a href="{{ route('inventory.transfers.index') }}" class="inline-flex items-center gap-2 py-1 text-xs font-semibold text-slate-500 transition hover:text-[#DC143C]"><span aria-hidden="true">&larr;</span> Kembali ke daftar distribusi</a>

{{-- Flash messages --}}
@if(session('status'))
    <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
        <span class="material-symbols-outlined mt-0.5 text-[18px] text-emerald-600">check_circle</span>
        <p>{{ session('status') }}</p>
    </div>
@endif
@if($errors->any())
    <div role="alert" class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
        <span class="material-symbols-outlined mt-0.5 text-[18px] text-rose-600">error</span>
        <p>{{ $errors->first() }}</p>
    </div>
@endif

@if(in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy_open'], true) && $canReceive)
    <section class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-700"><span class="material-symbols-outlined">inventory</span></span><div><p class="text-sm font-semibold text-slate-900">Barang sudah tiba di outlet?</p><p class="mt-1 text-xs text-slate-600">Konfirmasi jumlah yang benar-benar diterima agar stok outlet tercatat dan Staff Gudang mendapat kabar.</p></div></div>
        <a href="#receipt-form" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800"><span class="material-symbols-outlined text-[17px]">fact_check</span>Konfirmasi penerimaan</a>
    </section>
@endif

{{-- ═══════════ MAIN CARD: Header + Info + Items ═══════════ --}}
<article class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">

    {{-- Header --}}
    <header class="border-b border-slate-200 px-5 py-5 sm:px-7 sm:py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400"><span>Operasional Persediaan</span><span class="text-slate-300">/</span><span>Logistik</span><span class="text-slate-300">/</span><span class="text-[#DC143C]">Dokumen Transfer</span></div>
                <p class="mt-5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Perintah pemindahan barang antar lokasi</p>
                <h1 class="mt-1 font-poppins text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">Dokumen transfer</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500"><span class="font-semibold text-slate-600">No. transfer</span><span class="rounded bg-slate-100 px-2 py-1 font-mono text-[11px] font-semibold tracking-wide text-slate-800">{{ $transfer->reference }}</span><span class="text-slate-300">|</span><span>{{ $transfer->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span></p>
                <p class="mt-1.5 text-[11px] text-slate-500">Disiapkan oleh <span class="font-semibold text-slate-700">{{ $transfer->preparedBy?->name ?? $transfer->dispatcher?->name ?? 'Pengguna' }}</span></p>
            </div>
            <div class="flex flex-col items-start gap-2 sm:items-end">
                <span class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">Status dokumen</span>
                <span class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-[11px] font-bold {{ $currentStatus['style'] }}"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $currentStatus['label'] }}</span>
                @if($transfer->expected_arrival_at)
                    <span class="text-right text-[11px] text-slate-500">Estimasi tiba<br><strong class="font-semibold text-slate-800">{{ $transfer->expected_arrival_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</strong></span>
                @endif
            </div>
        </div>
    </header>

    <div class="grid grid-cols-3 border-b border-slate-200 bg-[#FAF9F7]">
        <div class="px-4 py-3 sm:px-7"><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Baris barang</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $transfer->items->count() }}</p></div>
        <div class="border-x border-slate-200 px-4 py-3 sm:px-7"><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Selesai diterima</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $completedItemLines }} <span class="text-xs font-normal text-slate-500">dari {{ $transfer->items->count() }} baris</span></p></div>
        <div class="px-4 py-3 sm:px-7"><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Bukti penerimaan</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $transfer->receipts->count() }} <span class="text-xs font-normal text-slate-500">catatan</span></p></div>
    </div>

    <section class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 px-4 py-5 sm:gap-6 sm:px-7" aria-label="Rute transfer">
        <div class="min-w-0 border-l-2 border-[#DC143C] pl-3"><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Dari gudang</p><p class="mt-1 break-words text-sm font-semibold text-slate-900 sm:text-base">{{ $transfer->sourceLocation->name }}</p><p class="mt-0.5 text-[10px] text-slate-500 sm:text-[11px]">Lokasi pengirim</p></div>
        <div class="flex flex-col items-center gap-1.5 text-[#DC143C]" aria-label="Dikirim menuju outlet"><span class="material-symbols-outlined text-xl sm:text-2xl">arrow_forward</span><span class="whitespace-nowrap text-[8px] font-bold uppercase tracking-[0.12em] text-slate-500 sm:text-[9px]">Dikirim ke</span></div>
        <div class="min-w-0 border-l-2 border-slate-300 pl-3"><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Tujuan outlet</p><p class="mt-1 break-words text-sm font-semibold text-slate-900 sm:text-base">{{ $transfer->destinationLocation->name }}</p><p class="mt-0.5 text-[10px] text-slate-500 sm:text-[11px]">Lokasi penerima</p></div>
    </section>

    <dl class="grid grid-cols-2 border-t border-slate-200 bg-white sm:grid-cols-3">
        <div class="border-b border-r border-slate-100 px-5 py-3 sm:px-7"><dt class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Disiapkan oleh</dt><dd class="mt-1 text-xs font-semibold text-slate-800">{{ $transfer->preparedBy?->name ?? $transfer->dispatcher?->name ?? 'Pengguna' }}</dd></div>
        <div class="border-b border-slate-100 px-5 py-3 sm:border-r sm:px-7"><dt class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">PIC gudang</dt><dd class="mt-1 text-xs font-semibold {{ $transfer->approver ? 'text-slate-800' : ($transfer->status === 'pending_approval' ? 'text-amber-800' : 'text-slate-500') }}">{{ $transfer->approver?->name ?? ($transfer->status === 'pending_approval' ? 'Menunggu persetujuan' : 'Belum tercatat') }}</dd></div>
        <div class="col-span-2 px-5 py-3 sm:col-span-1 sm:px-7"><dt class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Waktu persetujuan</dt><dd class="mt-1 text-xs font-semibold text-slate-800">{{ $transfer->approved_at ? $transfer->approved_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : 'Belum disetujui' }}</dd></div>
    </dl>

    @if($transfer->status === 'pending_approval' && $canApprove)
        <div class="border-t border-amber-200 bg-[#FBF7EE] px-5 py-4 sm:px-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-amber-300 bg-white text-amber-800"><span class="material-symbols-outlined text-[19px]">approval</span></span><div><p class="text-sm font-semibold text-slate-900">Persetujuan PIC Gudang diperlukan</p><p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-600">Periksa outlet tujuan dan jumlah pada rincian barang. Stok gudang baru dikurangi setelah transfer disetujui.</p></div></div>
            <form method="POST" action="{{ route('inventory.transfers.approve', $transfer) }}" class="sm:shrink-0" data-confirm-title="Setujui transfer ini?" data-confirm-message="Persetujuan akan mengurangi stok gudang dan mengubah transfer menjadi dalam perjalanan." data-confirm-button="Setujui & kirim">
                @csrf
                <label class="mb-2 block text-left text-[10px] font-semibold text-slate-700">Estimasi waktu tiba di outlet <span class="text-[#DC143C]">*</span>
                    <input type="datetime-local" name="expected_arrival_at" min="{{ now('Asia/Jakarta')->format('Y-m-d\\TH:i') }}" value="{{ old('expected_arrival_at') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-[#DC143C] focus:outline-none focus:ring-2 focus:ring-[#DC143C]/10 sm:w-64">
                </label>
                <button class="w-full rounded-lg bg-[#202B3C] px-5 py-3 text-xs font-bold text-white transition hover:bg-slate-800 sm:w-auto">Setujui &amp; kirim barang</button>
            </form>
            </div>
        </div>
    @elseif($transfer->status === 'pending_approval')
        <div class="flex items-center gap-3 border-t border-amber-200 bg-[#FBF7EE] px-5 py-4 text-xs text-amber-950 sm:px-7"><span class="material-symbols-outlined text-[18px]">schedule</span>Transfer menunggu persetujuan PIC Gudang. Stok gudang belum dikurangi.</div>
    @endif
</article>

@if($transfer->status === 'in_transit' && ! $transfer->expected_arrival_at && $canApprove)
    <form method="POST" action="{{ route('inventory.transfers.eta', $transfer) }}" class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm sm:p-6">
        @csrf
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-700"><span class="material-symbols-outlined">departure_board</span></span>
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-semibold text-slate-900">Lengkapi estimasi kedatangan</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">Transfer ini belum memiliki estimasi tiba. Isi waktunya agar Staff Outlet di lokasi tujuan mendapat informasi pengiriman.</p>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <label class="block text-[10px] font-semibold text-slate-700">Perkiraan waktu tiba <span class="text-[#DC143C]">*</span>
                        <input type="datetime-local" name="expected_arrival_at" min="{{ now('Asia/Jakarta')->format('Y-m-d\\TH:i') }}" value="{{ old('expected_arrival_at') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-[#DC143C] focus:outline-none focus:ring-2 focus:ring-[#DC143C]/10 sm:w-64">
                    </label>
                    <button class="rounded-lg bg-[#202B3C] px-4 py-2.5 text-xs font-bold text-white transition hover:bg-slate-800">Simpan &amp; beri tahu outlet</button>
                </div>
            </div>
        </div>
    </form>
@endif

{{-- ═══════════ ITEMS TABLE ═══════════ --}}
<section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
    <div class="flex items-end justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-7">
        <div>
            <p class="text-[9px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">Lampiran A</p>
            <h2 class="mt-1 text-base font-semibold text-slate-900">Rincian barang</h2>
            <p class="mt-1 text-[11px] text-slate-500">{{ $transfer->status === 'pending_approval' ? 'Daftar barang yang diajukan untuk dipindahkan.' : 'Jumlah per produk dalam satuan masing-masing.' }}</p>
        </div>
        <span class="shrink-0 font-mono text-[10px] text-slate-400">{{ $transfer->items->count() }} BARIS</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-[#F7F7F5] text-[9px] uppercase tracking-[0.12em] text-slate-500">
                <tr>
                    <th class="px-4 py-3 sm:px-7">Produk / batch</th>
                    <th class="px-4 py-3 text-right">{{ $transfer->status === 'pending_approval' ? 'Rencana' : 'Dikirim' }}</th>
                    <th class="px-4 py-3 text-right">Diterima</th>
                    <th class="px-4 py-3 text-right sm:px-7">Sisa</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transfer->items as $item)
                    @php($remaining = max(0, (float) $item->quantity - (float) $item->received_quantity))
                    <tr class="transition-colors hover:bg-[#FCFBF9]">
                        <td class="px-4 py-4 sm:px-7">
                            <p class="text-sm font-semibold text-slate-900">{{ $item->product->name }}</p>
                            <p class="mt-1 text-[10px] text-slate-500">
                                <span class="font-mono text-slate-400">{{ $item->product->sku }}</span>
                                <span class="px-1 text-slate-300">&middot;</span>
                                {{ $transfer->status === 'pending_approval' ? 'Batch dipilih setelah persetujuan' : ($item->sourceBatch?->batch_number ?: 'Tanpa batch') }}
                            </p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold tabular-nums text-slate-900">
                            {{ \App\Support\QuantityFormatter::format($item->quantity) }}
                            <span class="ml-1 text-[10px] font-normal text-slate-400">{{ $item->product->unit->symbol }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums">
                            <span class="font-semibold {{ (float)$item->received_quantity > 0 ? 'text-emerald-700' : 'text-slate-300' }}">
                                {{ \App\Support\QuantityFormatter::format($item->received_quantity) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums sm:px-7">
                            <span class="font-semibold {{ $remaining > 0 ? 'text-amber-700' : 'text-slate-300' }}">
                                {{ \App\Support\QuantityFormatter::format($remaining) }}
                            </span>
                            @if((float) $item->discrepancy_quantity > 0)
                                <span class="mt-1 block text-[10px] font-semibold text-rose-600">
                                    Discrepancy: {{ \App\Support\QuantityFormatter::format($item->discrepancy_quantity) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Footer notes --}}
    @if($transfer->stockOut || $transfer->notes)
        <div class="space-y-2 border-t border-slate-200 bg-[#FAF9F7] px-5 py-4 sm:px-7">
            @if($transfer->stockOut)
                <p class="text-xs text-gray-500">
                    <span class="material-symbols-outlined align-middle text-[14px] text-gray-400">output</span>
                    Mutasi keluar sumber: <span class="font-semibold text-gray-700">{{ $transfer->stockOut->reference }}</span>
                </p>
            @endif
            @if($transfer->notes)
                <div class="flex items-start gap-2 rounded-lg bg-slate-50 border border-slate-100 p-3 text-xs text-gray-600">
                    <span class="material-symbols-outlined text-[14px] text-gray-400 mt-0.5">sticky_note_2</span>
                    <p>{{ $transfer->notes }}</p>
                </div>
            @endif
        </div>
    @endif
</section>

{{-- ═══════════ RECEIPT HISTORY ═══════════ --}}
<section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
    <div class="flex items-end justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-7">
        <div>
            <p class="text-[9px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">Lampiran B</p>
            <h2 class="mt-1 text-base font-semibold text-slate-900">Riwayat penerimaan</h2>
            <p class="mt-1 text-[11px] text-slate-500">Catatan barang yang diterima oleh outlet tujuan.</p>
        </div>
        <span class="shrink-0 font-mono text-[10px] text-slate-400">{{ $transfer->receipts->count() }} CATATAN</span>
    </div>
    @forelse($transfer->receipts as $receipt)
        <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:px-7">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border {{ $receipt->is_final ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-blue-200 bg-blue-50 text-blue-700' }}">
                <span class="material-symbols-outlined text-[16px]">{{ $receipt->is_final ? 'verified' : 'local_shipping' }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <p class="text-sm font-semibold text-slate-900">{{ $receipt->received_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $receipt->is_final ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                        {{ $receipt->is_final ? 'Pemeriksaan Final' : 'Penerimaan Parsial' }}
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">Diterima oleh <span class="font-medium text-slate-700">{{ $receipt->receiver?->name ?? 'Pengguna' }}</span></p>
                @if($receipt->stockIn)
                    <p class="mt-1 text-xs">
                        <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] text-gray-600">
                            <span class="material-symbols-outlined text-[12px]">receipt_long</span>
                            {{ $receipt->stockIn->reference }}
                        </span>
                    </p>
                @endif
                @if($receipt->notes)
                    <p class="mt-1.5 text-xs text-gray-600 italic">&ldquo;{{ $receipt->notes }}&rdquo;</p>
                @endif
            </div>
        </div>
    @empty
        <div class="px-5 py-8 text-center sm:px-7">
            <p class="text-sm font-medium text-slate-600">Belum ada penerimaan tercatat.</p>
            <p class="mt-1 text-xs text-slate-400">Outlet tujuan dapat mengisi formulir penerimaan di bawah.</p>
        </div>
    @endforelse
</section>

{{-- ═══════════ DISCREPANCY SECTION ═══════════ --}}
@include('inventory.transfers._discrepancy-resolution')

{{-- ═══════════ RECEIVE FORM ═══════════ --}}
@if(in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy_open'], true) && $canReceive)
    <form id="receipt-form" method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" class="scroll-mt-5 rounded-2xl border border-gray-200 bg-white shadow-xs overflow-hidden">
        @csrf
        <div class="border-b border-gray-100 p-5 sm:p-6">
            <h2 class="font-semibold text-gray-900">Konfirmasi barang diterima</h2>
            <p class="mt-1 text-xs text-gray-500">Masukkan jumlah aktual per barang. Stok outlet diperbarui dan Staff Gudang menerima kabar setelah konfirmasi ini disimpan.</p>
        </div>

        <div class="divide-y divide-gray-100 p-5 sm:p-6 space-y-0">
            @foreach($transfer->items as $item)
                @php($remaining = max(0, (float) $item->quantity - (float) $item->received_quantity))
                <div class="py-3 first:pt-0 last:pb-0">
                    <label class="block">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $item->product->name }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">
                                maks. {{ \App\Support\QuantityFormatter::format($remaining) }} {{ $item->product->unit->symbol }}
                            </span>
                        </div>
                        <input type="number" name="items[{{ $item->id }}]" min="0" max="{{ $remaining }}" step="0.001" value="0"
                               class="mt-2 w-full rounded-xl border border-gray-300 bg-gray-50/50 p-3 text-sm font-medium text-gray-900 focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition">
                    </label>
                </div>
            @endforeach
        </div>

        @if($transfer->status !== 'discrepancy_open')
            <div class="border-t border-gray-100 p-5 sm:p-6 space-y-4">
                <label class="flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 p-3 cursor-pointer hover:bg-amber-100/60 transition">
                    <input type="checkbox" name="is_final" value="1" class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-amber-900">Tandai sebagai pemeriksaan final</span>
                        <p class="mt-0.5 text-xs text-amber-800">Sisa barang menjadi discrepancy terbuka untuk ditindaklanjuti Purchasing.</p>
                    </div>
                </label>

                <label class="block text-sm">
                    <span class="font-semibold text-gray-700">Alasan awal discrepancy</span>
                    <span class="text-xs text-gray-400 ml-1">(wajib jika pemeriksaan final menyisakan barang)</span>
                    <textarea name="discrepancy_reason" rows="2" placeholder="Jelaskan penyebab selisih..."
                              class="mt-2 w-full rounded-xl border border-gray-300 p-3 text-sm focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition"></textarea>
                </label>
            </div>
        @endif

        <div class="border-t border-gray-100 p-5 sm:p-6 space-y-4">
            <label class="block text-sm">
                <span class="font-semibold text-gray-700">Catatan penerimaan</span>
                <textarea name="notes" rows="2" placeholder="Catatan tambahan..."
                          class="mt-2 w-full rounded-xl border border-gray-300 p-3 text-sm focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition"></textarea>
            </label>
            <button class="w-full rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition">
                <span class="material-symbols-outlined align-middle text-[16px] mr-1">save</span>
                Konfirmasi penerimaan &amp; perbarui stok
            </button>
        </div>
    </form>
@endif

{{-- Footnote --}}
<div class="rounded-xl bg-slate-50 border border-slate-200/60 p-3.5 text-xs leading-relaxed text-gray-500">
    Transfer distribusi mencatat perpindahan barang antar lokasi. Saldo outlet diperbarui setelah penerimaan dicatat.
</div>
