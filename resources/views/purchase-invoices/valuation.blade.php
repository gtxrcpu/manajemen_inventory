@extends('layouts.dashboard')

@php
    $pageTitle = 'Valuasi Persediaan - Technolife';
    $exportUrl = route('finance.valuation.export', array_filter([
        'location_id' => $selectedLocation,
        'product_id' => $selectedProduct,
    ]));
    $confirmedCount = $rows->where('valuation_status', 'confirmed')->count();
    $provisionalCount = $rows->where('valuation_status', 'provisional')->count();
    $incompleteCount = $rows->where('valuation_status', 'incomplete')->count();
    $valuationStatusLabels = [
        'confirmed' => 'Terkonfirmasi',
        'provisional' => 'Sementara',
        'incomplete' => 'Belum lengkap',
    ];
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-10">
    <header class="space-y-1">
        <div class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/70 bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#DC143C]">
            <span class="material-symbols-outlined text-[14px]">calculate</span>
            Finance · Persediaan
        </div>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="font-poppins text-xl font-bold tracking-tight text-slate-900">Valuasi Persediaan</h1>
                <p class="mt-0.5 text-xs text-slate-500">Snapshot WAC · {{ $generatedAt->format('d M Y, H:i') }}</p>
            </div>
            <a href="{{ $exportUrl }}" class="inline-flex shrink-0 items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs active:scale-[0.98]">
                <span class="material-symbols-outlined text-[16px] text-emerald-700">download</span>CSV
            </a>
        </div>
    </header>

    <details class="group overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/80">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-3 text-xs font-semibold text-amber-950">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[17px] text-amber-700">info</span>Metode dan batas snapshot</span>
            <span class="material-symbols-outlined text-[18px] text-amber-700 transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <div class="border-t border-amber-200/80 px-3 pb-3 pt-2.5 text-[11px] leading-relaxed text-amber-900">
            Harga invoice setelah diskon dokumen dialokasikan proporsional menjadi biaya WAC. Pajak, ongkir, biaya lain, dan selisih total dokumen ditampilkan terpisah, tidak dikapitalisasi, dan dapat membuat nilai berstatus incomplete. Review invoice menentukan status confirmed atau provisional tanpa mengubah kuantitas ledger. Snapshot mengikuti waktu posting transaksi; tanggal operasional tidak mengurutkan ulang WAC dan data ini bukan rekonstruksi saldo historis.
        </div>
    </details>

    <section class="grid grid-cols-2 gap-2.5" aria-label="Ringkasan valuasi">
        <article class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Subtotal nilai*</p>
            <p class="mt-1 break-words font-poppins text-base font-bold leading-tight text-slate-900">Rp {{ number_format((float) $totalValue, 2, ',', '.') }}</p>
            <p class="mt-1 text-[10px] text-slate-500">Dengan harga tersedia</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Terkonfirmasi</p>
            <p class="mt-1 font-poppins text-xl font-bold text-emerald-700">{{ number_format($confirmedCount) }}</p>
            <p class="text-[10px] text-slate-500">baris valuasi</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-700">Provisional</p>
            <p class="mt-1 font-poppins text-xl font-bold text-amber-700">{{ number_format($provisionalCount) }}</p>
            <p class="text-[10px] text-slate-500">menunggu review</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-2xs">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-700">Belum lengkap</p>
            <p class="mt-1 font-poppins text-xl font-bold text-rose-700">{{ number_format($incompleteCount) }}</p>
            <p class="text-[10px] text-slate-500">perlu sumber harga</p>
        </article>
    </section>

    <form method="GET" action="{{ route('finance.valuation') }}" class="space-y-3 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-2.5">
            <span class="material-symbols-outlined text-[18px] text-[#DC143C]">tune</span>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Filter valuasi</h2>
        </div>
        <label class="block text-[11px] font-semibold text-slate-600">Lokasi
            <select name="location_id" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-medium text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                <option value="">Semua lokasi</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) $selectedLocation === (string) $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-[11px] font-semibold text-slate-600">Produk
            <select name="product_id" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-medium text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                <option value="">Semua produk</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) $selectedProduct === (string) $product->id)>{{ $product->sku }} · {{ $product->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
            <button class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#DC143C] px-3 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-[#b81032]">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>Terapkan
            </button>
            <a href="{{ route('finance.valuation') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <section class="space-y-3" aria-label="Rincian valuasi">
        <div class="flex items-end justify-between gap-3 px-0.5">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Rincian valuasi</h2>
                <p class="text-[11px] text-slate-500">{{ number_format($rows->count()) }} baris pada filter ini</p>
            </div>
        </div>
        @forelse($rows as $row)
            @php
                $statusClass = $row['valuation_status'] === 'confirmed'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : ($row['valuation_status'] === 'provisional'
                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                        : 'bg-rose-50 text-rose-700 border-rose-200');
            @endphp
            <article class="space-y-3 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-slate-900">{{ $row['product_name'] }}</h3>
                        <p class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $row['sku'] }}</p>
                        <p class="mt-1 flex items-center gap-1 text-xs text-slate-600"><span class="material-symbols-outlined text-[15px] text-slate-400">location_on</span>{{ $row['location_name'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border px-2 py-1 text-[10px] font-semibold {{ $statusClass }}">{{ $valuationStatusLabels[$row['valuation_status']] ?? ucfirst($row['valuation_status']) }}</span>
                </div>

                <dl class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-xs">
                    <div><dt class="text-[10px] text-slate-500">Saldo</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ \App\Support\QuantityFormatter::format($row['quantity']) }} {{ $row['unit_symbol'] }}</dd></div>
                    <div><dt class="text-[10px] text-slate-500">WAC per unit</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $row['unit_cost'] === null ? 'Belum lengkap' : 'Rp '.number_format((float) $row['unit_cost'], 4, ',', '.') }}</dd></div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-2.5"><dt class="text-[10px] text-slate-500">Nilai terhitung</dt><dd class="mt-0.5 font-poppins text-sm font-bold text-slate-900">Rp {{ number_format((float) $row['value'], 2, ',', '.') }}</dd></div>
                </dl>

                <div class="space-y-1.5 border-t border-slate-100 pt-2.5 text-[11px] leading-relaxed">
                    <p class="text-slate-600"><span class="font-semibold text-slate-700">Sumber:</span> {{ implode(', ', $row['source_invoices'] ?? []) ?: 'Belum diketahui' }}</p>
                    @if(($row['unpriced_quantity'] ?? 0) > 0)<p class="text-rose-700">{{ \App\Support\QuantityFormatter::format($row['unpriced_quantity']) }} {{ $row['unit_symbol'] }} belum memiliki harga.</p>@endif
                    @if(($row['provisional_quantity'] ?? 0) > 0)<p class="text-amber-700">{{ \App\Support\QuantityFormatter::format($row['provisional_quantity']) }} {{ $row['unit_symbol'] }} memakai harga provisional.</p>@endif
                    @if(($row['unallocated_cost'] ?? 0) > 0)<p class="text-amber-700">Biaya belum dialokasikan: Rp {{ number_format((float) $row['unallocated_cost'], 2, ',', '.') }}</p>@endif
                    @if(($row['document_variance'] ?? 0) > 0)<p class="text-rose-700">Selisih dokumen: Rp {{ number_format((float) $row['document_variance'], 2, ',', '.') }}</p>@endif
                    @if(!empty($row['transfer_references']))<p class="text-blue-700">Transfer: {{ implode(', ', array_unique($row['transfer_references'])) }}</p>@endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <span class="material-symbols-outlined text-[32px] text-slate-300">inventory_2</span>
                <p class="mt-2 text-sm font-semibold text-slate-800">Tidak ada saldo untuk filter ini</p>
                <p class="mt-1 text-xs text-slate-500">Coba pilih lokasi atau produk yang berbeda.</p>
            </div>
        @endforelse
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-5 pb-10">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-xs text-slate-500">
                <span>Manajemen Inventaris</span>
                <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
                <span class="font-semibold text-slate-800">Finance · Valuasi</span>
            </nav>
            <h1 class="font-poppins text-2xl font-bold tracking-tight text-slate-900">Valuasi Persediaan</h1>
            <p class="mt-1 text-xs text-slate-500">Snapshot WAC per produk dan lokasi · {{ $generatedAt->format('d M Y, H:i') }}</p>
        </div>
        <a href="{{ $exportUrl }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-[17px] text-emerald-700">download</span>Export CSV
        </a>
    </header>

    <details class="group overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/80">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-3.5 text-sm font-semibold text-amber-950">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[19px] text-amber-700">info</span>Metode dan batas interpretasi</span>
            <span class="material-symbols-outlined text-[19px] text-amber-700 transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <div class="border-t border-amber-200/80 px-5 py-3.5 text-xs leading-relaxed text-amber-950">
            <p>Harga invoice Purchasing setelah diskon dokumen dialokasikan proporsional menjadi biaya WAC. Pajak, ongkir, biaya lain, dan selisih total dokumen tidak dikapitalisasi; semuanya ditampilkan terpisah dan dapat membuat status incomplete. Review invoice menentukan confirmed atau provisional, tetapi tidak mengubah kuantitas ledger.</p>
            <p class="mt-1.5">Urutan replay mengikuti waktu posting transaksi lalu ID movement. Tanggal operasional yang diinput tidak mengurutkan ulang WAC. Snapshot ini bukan laporan tutup buku dan tidak mendukung cutoff atau rekonstruksi saldo historis.</p>
        </div>
    </details>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan valuasi">
        <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500"><span class="text-[11px] font-semibold uppercase tracking-wider">Subtotal nilai*</span><span class="material-symbols-outlined text-[19px] text-[#DC143C]">payments</span></div>
            <p class="mt-2 break-words font-poppins text-xl font-bold leading-tight text-slate-900">Rp {{ number_format((float) $totalValue, 2, ',', '.') }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Nilai dengan harga tersedia</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500"><span class="text-[11px] font-semibold uppercase tracking-wider">Terkonfirmasi</span><span class="material-symbols-outlined text-[19px] text-emerald-700">verified</span></div>
            <p class="mt-2 font-poppins text-2xl font-bold text-emerald-700">{{ number_format($confirmedCount) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">baris valuasi</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500"><span class="text-[11px] font-semibold uppercase tracking-wider">Provisional</span><span class="material-symbols-outlined text-[19px] text-amber-700">pending_actions</span></div>
            <p class="mt-2 font-poppins text-2xl font-bold text-amber-700">{{ number_format($provisionalCount) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">menunggu review Finance</p>
        </article>
        <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500"><span class="text-[11px] font-semibold uppercase tracking-wider">Belum lengkap</span><span class="material-symbols-outlined text-[19px] text-rose-700">warning</span></div>
            <p class="mt-2 font-poppins text-2xl font-bold text-rose-700">{{ number_format($incompleteCount) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">perlu sumber harga</p>
        </article>
    </section>

    <form method="GET" action="{{ route('finance.valuation') }}" class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-[#DC143C]"><span class="material-symbols-outlined text-[18px]">tune</span></span>
                <div><h2 class="text-sm font-bold text-slate-900">Filter valuasi</h2><p class="text-[11px] text-slate-500">Persempit hasil berdasarkan lokasi atau produk</p></div>
            </div>
            <span class="text-xs text-slate-500">{{ number_format($rows->count()) }} baris pada filter ini</span>
        </div>
        <div class="mt-3 flex flex-wrap items-end gap-3">
            <label class="block text-[11px] font-semibold text-slate-600">Lokasi
                <select name="location_id" class="mt-1 h-10 min-w-56 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-medium text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $selectedLocation === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-[11px] font-semibold text-slate-600">Produk
                <select name="product_id" class="mt-1 h-10 min-w-72 max-w-[32rem] rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-medium text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                    <option value="">Semua produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) $selectedProduct === (string) $product->id)>{{ $product->sku }} · {{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-[#DC143C] px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-[#b81032]">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>Terapkan
            </button>
            <a href="{{ route('finance.valuation') }}" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs" aria-label="Rincian valuasi persediaan">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 bg-slate-50/50 px-4 py-3.5 sm:px-6">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-700 shadow-2xs"><span class="material-symbols-outlined text-[18px]">table_chart</span></span>
                <div><h2 class="text-sm font-bold text-slate-900">Rincian valuasi</h2><p class="text-[11px] text-slate-500">{{ number_format($rows->count()) }} baris pada filter ini</p></div>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-2xs">Snapshot {{ $generatedAt->format('d M Y, H:i') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1260px] text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3.5">Produk / SKU</th>
                        <th class="px-4 py-3.5">Lokasi</th>
                        <th class="px-4 py-3.5 text-right">Saldo</th>
                        <th class="px-4 py-3.5 text-right">WAC / unit</th>
                        <th class="px-4 py-3.5 text-right">Nilai terhitung</th>
                        <th class="px-4 py-3.5">Sumber invoice / receipt</th>
                        <th class="px-4 py-3.5 text-right">Biaya belum dialokasikan</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($rows as $row)
                        @php
                            $statusClass = $row['valuation_status'] === 'confirmed'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : ($row['valuation_status'] === 'provisional'
                                    ? 'bg-amber-50 text-amber-800 border-amber-200'
                                    : 'bg-rose-50 text-rose-700 border-rose-200');
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $row['product_name'] }}</div>
                                <div class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $row['sku'] }}</div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-slate-800">{{ $row['location_name'] }}</div>
                                @if(!empty($row['transfer_references']))<div class="mt-1 max-w-48 truncate text-[10px] text-blue-700" title="{{ implode(', ', array_unique($row['transfer_references'])) }}">Transfer: {{ implode(', ', array_unique($row['transfer_references'])) }}</div>@endif
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-semibold text-slate-900">{{ \App\Support\QuantityFormatter::format($row['quantity']) }} {{ $row['unit_symbol'] }}</td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">{{ $row['unit_cost'] === null ? 'Belum lengkap' : 'Rp '.number_format((float) $row['unit_cost'], 4, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="font-semibold text-slate-900">Rp {{ number_format((float) $row['value'], 2, ',', '.') }}</div>
                                @if(($row['unpriced_quantity'] ?? 0) > 0)<div class="mt-0.5 text-[10px] text-rose-700">{{ \App\Support\QuantityFormatter::format($row['unpriced_quantity']) }} {{ $row['unit_symbol'] }} belum dinilai</div>@endif
                                @if(($row['provisional_quantity'] ?? 0) > 0)<div class="mt-0.5 text-[10px] text-amber-700">{{ \App\Support\QuantityFormatter::format($row['provisional_quantity']) }} provisional</div>@endif
                            </td>
                            <td class="max-w-64 px-4 py-3.5 text-slate-600" title="{{ implode(', ', $row['source_invoices'] ?? []) }}"><div class="truncate">{{ implode(', ', $row['source_invoices'] ?? []) ?: 'Belum diketahui' }}</div></td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="text-slate-800">Rp {{ number_format((float) ($row['unallocated_cost'] ?? 0), 2, ',', '.') }}</div>
                                @if(($row['document_variance'] ?? 0) > 0)<div class="mt-0.5 text-[10px] text-rose-700">Selisih dokumen: Rp {{ number_format((float) $row['document_variance'], 2, ',', '.') }}</div>@endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $statusClass }}">{{ $valuationStatusLabels[$row['valuation_status']] ?? ucfirst($row['valuation_status']) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-14 text-center">
                            <span class="material-symbols-outlined text-[32px] text-slate-300">inventory_2</span>
                            <p class="mt-2 text-sm font-semibold text-slate-800">Tidak ada saldo untuk filter ini</p>
                            <p class="mt-1 text-xs text-slate-500">Coba pilih lokasi atau produk yang berbeda.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <p class="px-1 text-[11px] text-slate-500">* Subtotal tidak mencakup kuantitas tanpa harga maupun biaya dan selisih dokumen yang belum dialokasikan.</p>
</section>
@endsection
