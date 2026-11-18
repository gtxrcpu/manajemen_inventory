@extends('layouts.dashboard')

@php
    $pageTitle = $product->name . ' - Detail Persediaan · Technolife';
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-10">
    <a href="{{ route('inventory.stock.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 transition-colors hover:text-[#DC143C]">
        <span class="material-symbols-outlined text-[17px]">arrow_back</span>Kembali ke Data Stock
    </a>

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs">
        <div class="p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $product->category->name }}</p>
                    <h1 class="mt-1 text-lg font-bold leading-snug text-slate-900">{{ $product->name }}</h1>
                    <p class="mt-1 font-mono text-[11px] text-slate-500">{{ $product->sku }} <span class="font-sans">· {{ $product->unit->name }}</span></p>
                </div>
                <span @class(['shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-semibold', 'border-emerald-200 bg-emerald-50 text-emerald-700' => $product->is_active, 'border-slate-200 bg-slate-100 text-slate-600' => ! $product->is_active])>
                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="mt-4 rounded-2xl bg-rose-50/80 p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-rose-900">Saldo saat ini</p>
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-rose-800"><span class="material-symbols-outlined text-[14px]">location_on</span>{{ $scopeLocations->isNotEmpty() ? $scopeLocations->implode(', ') : 'Semua lokasi' }}</span>
                </div>
                <p class="mt-1 font-poppins text-3xl font-bold tracking-tight text-[#DC143C]">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} <span class="text-base font-semibold text-rose-800">{{ $product->unit->symbol }}</span></p>
            </div>
        </div>

        <div class="grid grid-cols-2 border-t border-slate-100">
            <div class="border-b border-r border-slate-100 px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Total masuk</p>
                <p class="mt-1 font-semibold text-emerald-700">{{ \App\Support\QuantityFormatter::format($totalIn) }} <span class="text-xs font-normal text-slate-500">{{ $product->unit->symbol }}</span></p>
            </div>
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Total keluar</p>
                <p class="mt-1 font-semibold text-rose-700">{{ \App\Support\QuantityFormatter::format($totalOut) }} <span class="text-xs font-normal text-slate-500">{{ $product->unit->symbol }}</span></p>
            </div>
            <div class="border-r border-slate-100 px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Catatan mutasi</p>
                <p class="mt-1 font-semibold text-slate-900">{{ number_format($movementCount) }}</p>
            </div>
            <div class="px-4 py-3">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Batch tercatat</p>
                <p class="mt-1 font-semibold text-slate-900">{{ number_format($batches->count()) }}</p>
            </div>
        </div>
    </section>

    @if($product->description)
        <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi produk</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $product->description }}</p>
        </section>
    @endif

    <details class="group overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4">
            <div><h2 class="text-sm font-bold text-slate-900">Batch &amp; kedaluwarsa</h2><p class="mt-0.5 text-[11px] text-slate-500">{{ number_format($batches->count()) }} batch · saldo, karantina, dan ketersediaan</p></div>
            <span class="material-symbols-outlined text-[19px] text-slate-400 transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <div class="divide-y divide-slate-100 border-t border-slate-100">
            @forelse($batches as $batch)
                @php
                    $expiry = $batch->expiry_date ? \Illuminate\Support\Carbon::parse($batch->expiry_date) : null;
                    $ageDays = $batch->received_at ? max(0, (int) \Illuminate\Support\Carbon::parse($batch->received_at)->startOfDay()->diffInDays(today())) : null;
                    $available = max(0, (float) $batch->remaining - (float) $batch->quarantined);
                    $expiryClass = $expiry && $expiry->isBefore(today()) ? 'text-rose-700' : ($expiry && $expiry->lessThanOrEqualTo(today()->addDays(30)) ? 'text-amber-700' : 'text-slate-500');
                @endphp
                <article class="space-y-2 p-4">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="truncate text-sm font-semibold text-slate-900">{{ $batch->batch_number ?: 'Batch tanpa nomor' }}</h3><p class="mt-0.5 text-[11px] text-slate-500">{{ $batch->location_name }} · diterima {{ \Illuminate\Support\Carbon::parse($batch->received_at)->format('d M Y') }}</p></div><p class="shrink-0 text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format((float) $batch->remaining) }} {{ $product->unit->symbol }}</p></div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600"><span>Karantina {{ \App\Support\QuantityFormatter::format((float) $batch->quarantined) }}</span><span>Tersedia {{ \App\Support\QuantityFormatter::format($available) }} {{ $product->unit->symbol }}</span></div>
                    <p class="text-[11px] {{ $expiryClass }}">{{ $expiry ? 'Kedaluwarsa '.$expiry->format('d M Y').($expiry->isBefore(today()) ? ' · Sudah kedaluwarsa' : ($expiry->lessThanOrEqualTo(today()->addDays(30)) ? ' · Segera kedaluwarsa' : '')) : 'Tanpa tanggal kedaluwarsa'.($ageDays !== null ? ' · umur stok '.$ageDays.' hari' : '') }}</p>
                </article>
            @empty
                <p class="border-t border-slate-100 p-4 text-xs text-slate-500">Belum ada batch yang tercatat.</p>
            @endforelse
        </div>
    </details>

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div><h2 class="text-sm font-bold text-slate-900">Mutasi terbaru</h2><p class="mt-0.5 text-[11px] text-slate-500">20 catatan terakhir dari {{ number_format($movementCount) }}</p></div>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><span class="material-symbols-outlined text-[18px]">timeline</span></span>
        </div>
        @forelse($movements as $movement)
            @php
                $isInbound = $movement->type === 'in';
            @endphp
            <article class="border-b border-slate-100 p-4 last:border-0">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $isInbound ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}"><span class="material-symbols-outlined text-[13px]">{{ $isInbound ? 'south' : 'north' }}</span>{{ $movement->transaction_type }}</span>
                        <p class="mt-1.5 truncate font-mono text-[11px] font-semibold text-slate-800">{{ $movement->transaction_reference }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $movement->created_at->format('d M Y, H:i') }} · {{ $movement->location?->name ?? 'Lokasi tidak tercatat' }}</p>
                    </div>
                    <p class="shrink-0 text-sm font-bold {{ $isInbound ? 'text-emerald-700' : 'text-rose-700' }}">{{ $isInbound ? '+' : '−' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $product->unit->symbol }}</p>
                </div>
                @if($movement->note)<p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $movement->note }}</p>@endif
                <p class="mt-1 text-[10px] text-slate-500">Dicatat oleh {{ $movement->user?->name ?? 'Sistem' }}</p>
            </article>
        @empty
            <div class="p-8 text-center"><span class="material-symbols-outlined text-[30px] text-slate-300">receipt_long</span><p class="mt-2 text-sm font-semibold text-slate-700">Belum ada mutasi</p><p class="mt-1 text-xs text-slate-500">Pergerakan stok akan tercatat di sini.</p></div>
        @endforelse
    </section>

    @if(auth()->user()->hasRole('super_admin'))
        <a href="{{ route('admin.master-data.edit', ['products', $product->id]) }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50"><span class="material-symbols-outlined text-[18px]">edit</span>Edit data produk</a>
    @endif
</main>
@endsection

@section('desktop-content')
<section class="min-w-0 space-y-5 pb-10">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <nav class="mb-1 flex items-center gap-1.5 text-xs text-slate-500"><a href="{{ route('inventory.stock.index') }}" class="transition-colors hover:text-[#DC143C]">Data Stock</a><span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span><span class="truncate font-semibold text-slate-800">{{ $product->name }}</span></nav>
            <h1 class="font-poppins text-2xl font-bold tracking-tight text-slate-900">{{ $product->name }}</h1>
            <p class="mt-1 text-xs text-slate-500"><span class="font-mono">{{ $product->sku }}</span> · {{ $product->category->name }} · {{ $product->unit->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span @class(['rounded-full border px-2.5 py-1 text-[11px] font-semibold', 'border-emerald-200 bg-emerald-50 text-emerald-700' => $product->is_active, 'border-slate-200 bg-slate-100 text-slate-600' => ! $product->is_active])>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            @if(auth()->user()->hasRole('super_admin'))<a href="{{ route('admin.master-data.edit', ['products', $product->id]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50"><span class="material-symbols-outlined text-[16px]">edit</span>Edit produk</a>@endif
        </div>
    </header>

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(250px,0.8fr)]" aria-label="Ringkasan stok produk">
        <article class="rounded-2xl border border-rose-200/70 bg-rose-50/70 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wider text-rose-900">Saldo saat ini</p><span class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-medium text-slate-600"><span class="material-symbols-outlined text-[15px] text-[#DC143C]">location_on</span>{{ $scopeLocations->isNotEmpty() ? $scopeLocations->implode(', ') : 'Semua lokasi' }}</span></div>
            <p class="mt-2 font-poppins text-4xl font-bold tracking-tight text-[#DC143C]">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} <span class="text-xl font-semibold text-rose-900">{{ $product->unit->symbol }}</span></p>
            <p class="mt-1 text-xs text-slate-600">Saldo dihitung dari seluruh mutasi pada cakupan lokasi ini.</p>
        </article>
        <div class="grid grid-cols-2 gap-3">
            <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs"><div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total masuk</span><span class="material-symbols-outlined text-[18px] text-emerald-700">south</span></div><p class="mt-2 font-poppins text-lg font-bold text-emerald-700">{{ \App\Support\QuantityFormatter::format($totalIn) }} <span class="text-xs font-medium text-slate-500">{{ $product->unit->symbol }}</span></p></article>
            <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs"><div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total keluar</span><span class="material-symbols-outlined text-[18px] text-rose-700">north</span></div><p class="mt-2 font-poppins text-lg font-bold text-rose-700">{{ \App\Support\QuantityFormatter::format($totalOut) }} <span class="text-xs font-medium text-slate-500">{{ $product->unit->symbol }}</span></p></article>
            <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs"><div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Catatan mutasi</span><span class="material-symbols-outlined text-[18px] text-slate-600">receipt_long</span></div><p class="mt-2 font-poppins text-lg font-bold text-slate-900">{{ number_format($movementCount) }}</p></article>
            <article class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs"><div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Batch</span><span class="material-symbols-outlined text-[18px] text-blue-700">inventory_2</span></div><p class="mt-2 font-poppins text-lg font-bold text-slate-900">{{ number_format($batches->count()) }}</p></article>
        </div>
    </section>

    @if($product->description)
        <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs"><h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Deskripsi produk</h2><p class="mt-1.5 max-w-4xl text-sm leading-relaxed text-slate-600">{{ $product->description }}</p></section>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(300px,0.9fr)_minmax(0,1.6fr)]">
        <details class="group overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs" open>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div><h2 class="text-sm font-bold text-slate-900">Batch &amp; kedaluwarsa</h2><p class="mt-0.5 text-[11px] text-slate-500">Sisa, karantina, dan ketersediaan per batch</p></div>
                <span class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">{{ number_format($batches->count()) }}</span><span class="material-symbols-outlined text-[18px] text-slate-400 transition-transform group-open:rotate-180">expand_more</span></span>
            </summary>
            <div class="divide-y divide-slate-100">
                @forelse($batches as $batch)
                    @php
                        $expiry = $batch->expiry_date ? \Illuminate\Support\Carbon::parse($batch->expiry_date) : null;
                        $ageDays = $batch->received_at ? max(0, (int) \Illuminate\Support\Carbon::parse($batch->received_at)->startOfDay()->diffInDays(today())) : null;
                        $available = max(0, (float) $batch->remaining - (float) $batch->quarantined);
                        $expiryClass = $expiry && $expiry->isBefore(today()) ? 'text-rose-700' : ($expiry && $expiry->lessThanOrEqualTo(today()->addDays(30)) ? 'text-amber-700' : 'text-slate-500');
                    @endphp
                    <article class="space-y-2 px-5 py-3.5">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="truncate text-sm font-semibold text-slate-900">{{ $batch->batch_number ?: 'Batch tanpa nomor' }}</h3><p class="mt-0.5 text-[11px] text-slate-500">{{ $batch->location_name }} · {{ \Illuminate\Support\Carbon::parse($batch->received_at)->format('d M Y') }}</p></div><p class="shrink-0 text-sm font-bold text-slate-900">{{ \App\Support\QuantityFormatter::format((float) $batch->remaining) }} {{ $product->unit->symbol }}</p></div>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-600"><span>Karantina {{ \App\Support\QuantityFormatter::format((float) $batch->quarantined) }}</span><span>Tersedia {{ \App\Support\QuantityFormatter::format($available) }} {{ $product->unit->symbol }}</span></div>
                        <p class="text-[11px] {{ $expiryClass }}">{{ $expiry ? 'Kedaluwarsa '.$expiry->format('d M Y').($expiry->isBefore(today()) ? ' · Sudah kedaluwarsa' : ($expiry->lessThanOrEqualTo(today()->addDays(30)) ? ' · Segera kedaluwarsa' : '')) : 'Tanpa tanggal kedaluwarsa'.($ageDays !== null ? ' · umur stok '.$ageDays.' hari' : '') }}</p>
                    </article>
                @empty
                    <p class="p-5 text-xs text-slate-500">Belum ada batch yang tercatat.</p>
                @endforelse
            </div>
        </details>

        <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 bg-slate-50/50 px-5 py-4">
                <div><h2 class="text-sm font-bold text-slate-900">Mutasi terbaru</h2><p class="mt-0.5 text-[11px] text-slate-500">20 catatan terakhir dari {{ number_format($movementCount) }}</p></div>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-600 shadow-2xs"><span class="material-symbols-outlined text-[18px]">timeline</span></span>
            </div>
            @if($movements->isEmpty())
                <div class="p-12 text-center"><span class="material-symbols-outlined text-[32px] text-slate-300">receipt_long</span><p class="mt-2 text-sm font-semibold text-slate-700">Belum ada mutasi</p><p class="mt-1 text-xs text-slate-500">Pergerakan stok akan tercatat di sini.</p></div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[650px] text-left text-xs">
                        <thead class="border-b border-slate-200 bg-white text-[10px] font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Mutasi</th><th class="px-4 py-3">Referensi</th><th class="px-4 py-3">Lokasi / Petugas</th><th class="px-5 py-3 text-right">Jumlah</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($movements as $movement)
                                @php
                                    $isInbound = $movement->type === 'in';
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <td class="px-5 py-3.5"><span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $isInbound ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}"><span class="material-symbols-outlined text-[13px]">{{ $isInbound ? 'south' : 'north' }}</span>{{ $movement->transaction_type }}</span><p class="mt-1.5 text-[10px] text-slate-500">{{ $movement->created_at->format('d M Y, H:i') }}</p></td>
                                    <td class="px-4 py-3.5"><p class="max-w-48 truncate font-mono font-semibold text-slate-800" title="{{ $movement->transaction_reference }}">{{ $movement->transaction_reference }}</p>@if($movement->note)<p class="mt-1 max-w-52 truncate text-[10px] text-slate-500" title="{{ $movement->note }}">{{ $movement->note }}</p>@endif</td>
                                    <td class="px-4 py-3.5"><p class="text-slate-700">{{ $movement->location?->name ?? 'Lokasi tidak tercatat' }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $movement->user?->name ?? 'Sistem' }}</p></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold {{ $isInbound ? 'text-emerald-700' : 'text-rose-700' }}">{{ $isInbound ? '+' : '−' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $product->unit->symbol }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</section>
@endsection
