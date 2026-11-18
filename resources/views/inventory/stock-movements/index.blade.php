@extends('layouts.dashboard')

@php
    $pageTitle = 'Riwayat Mutasi Stok - Technolife';
    $typeLabels = [
        'stock_in' => 'Stock In',
        'stock_out' => 'Stock Out',
        'stock_opname' => 'Stock Opname',
        'adjustment' => 'Adjustment',
    ];
    $hasActiveFilters = collect($filters ?? [])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-10">
    <header>
        <div class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/70 bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#DC143C]">
            <span class="material-symbols-outlined text-[14px]">swap_horiz</span>
            Manajemen Inventaris
        </div>
        <h1 class="mt-1 font-poppins text-xl font-bold tracking-tight text-slate-900">Riwayat Mutasi Stok</h1>
        <p class="mt-0.5 text-xs text-slate-500">Perubahan saldo per produk dan lokasi.</p>
    </header>

    <details class="group overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xs" @if($hasActiveFilters) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-3.5 text-xs font-semibold text-slate-800">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-[#DC143C]">tune</span>Filter mutasi @if($hasActiveFilters)<span class="h-1.5 w-1.5 rounded-full bg-[#DC143C]"></span>@endif</span>
            <span class="material-symbols-outlined text-[18px] text-slate-400 transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <form method="GET" action="{{ route('inventory.movements.index') }}" class="space-y-3 border-t border-slate-100 p-3.5">
            <label class="block text-[11px] font-semibold text-slate-600">Nomor referensi atau catatan
                <input name="reference" value="{{ $filters['reference'] ?? '' }}" placeholder="Cari referensi atau catatan..." class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-[#DC143C] focus:bg-white focus:outline-none">
            </label>
            <label class="block text-[11px] font-semibold text-slate-600">Produk
                <select name="product_id" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                    <option value="">Semua produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} · {{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-[11px] font-semibold text-slate-600">Lokasi
                <select name="location_id" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua lokasi</option>@endunless
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block text-[11px] font-semibold text-slate-600">Jenis
                    <select name="type" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-2.5 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                        <option value="">Semua jenis</option>
                        @foreach($typeLabels as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-[11px] font-semibold text-slate-600">Petugas
                    <select name="user_id" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-2.5 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none">
                        <option value="">Semua petugas</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label class="block text-[11px] font-semibold text-slate-600">Dari tanggal<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-2.5 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none"></label>
                <label class="block text-[11px] font-semibold text-slate-600">Sampai tanggal<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-2.5 text-xs text-slate-800 focus:border-[#DC143C] focus:bg-white focus:outline-none"></label>
            </div>
            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                <button class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#DC143C] px-3 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-[#b81032]"><span class="material-symbols-outlined text-[16px]">filter_alt</span>Terapkan</button>
                <a href="{{ route('inventory.movements.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </details>

    <div class="flex items-center justify-between rounded-xl bg-slate-100/80 px-3 py-2 text-[11px] text-slate-600">
        <span>{{ number_format($movements->total()) }} mutasi ditemukan</span>
        <span>Halaman {{ $movements->currentPage() }} / {{ max(1, $movements->lastPage()) }}</span>
    </div>

    <section class="space-y-2.5" aria-label="Daftar mutasi stok">
        @forelse($movements as $movement)
            @php
                $isInbound = $movement->type === 'in';
                $typeClass = $isInbound ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200';
                $directionLabel = $isInbound ? 'Masuk' : 'Keluar';
            @endphp
            <a href="{{ route('inventory.movements.show', $movement) }}" class="block rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs transition-colors active:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $typeClass }}"><span class="material-symbols-outlined text-[13px]">{{ $isInbound ? 'south' : 'north' }}</span>{{ $directionLabel }}</span>
                            <span class="text-[10px] font-medium text-slate-500">{{ $movement->transaction_type }}</span>
                        </div>
                        <h2 class="mt-2 truncate text-sm font-bold text-slate-900">{{ $movement->product->name }}</h2>
                        <p class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $movement->product->sku }}</p>
                    </div>
                    <p class="shrink-0 whitespace-nowrap text-sm font-bold {{ $isInbound ? 'text-emerald-700' : 'text-rose-700' }}">{{ $isInbound ? '+' : '−' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $movement->product->unit->symbol }}</p>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-xs">
                    <div><p class="text-[10px] text-slate-500">Saldo sebelum</p><p class="mt-0.5 font-semibold text-slate-800">{{ \App\Support\QuantityFormatter::format($movement->before_quantity) }} {{ $movement->product->unit->symbol }}</p></div>
                    <div><p class="text-[10px] text-slate-500">Saldo sesudah</p><p class="mt-0.5 font-semibold text-slate-800">{{ \App\Support\QuantityFormatter::format($movement->after_quantity) }} {{ $movement->product->unit->symbol }}</p></div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-2.5 text-[11px] text-slate-500">
                    <span class="min-w-0 truncate font-mono text-slate-700">{{ $movement->transaction_reference ?: 'Tanpa referensi' }}</span>
                    <span class="shrink-0">{{ $movement->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between gap-3 text-[11px] text-slate-500">
                    <span class="min-w-0 truncate">{{ $movement->location->name }}</span>
                    <span class="shrink-0">{{ $movement->user->name ?? 'Sistem' }}</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <span class="material-symbols-outlined text-[32px] text-slate-300">swap_horiz</span>
                <p class="mt-2 text-sm font-semibold text-slate-800">Tidak ada mutasi ditemukan</p>
                <p class="mt-1 text-xs text-slate-500">Coba ubah filter atau reset pencarian.</p>
            </div>
        @endforelse
    </section>
    @if($movements->hasPages())<div class="pt-1">{{ $movements->links() }}</div>@endif
</main>
@endsection

@section('desktop-content')
<section class="space-y-5 pb-10">
    <header class="flex items-start justify-between gap-4">
        <div>
            <nav class="mb-1 flex items-center gap-1.5 text-xs text-slate-500">
                <span>Manajemen Inventaris</span><span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span><span class="font-semibold text-slate-800">Riwayat Mutasi</span>
            </nav>
            <h1 class="font-poppins text-2xl font-bold tracking-tight text-slate-900">Riwayat Mutasi Stok</h1>
            <p class="mt-1 text-xs text-slate-500">Perubahan saldo per produk dan lokasi, diurutkan dari mutasi terbaru.</p>
        </div>
        <a href="{{ route('inventory.transactions.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-50">
            <span class="material-symbols-outlined text-[16px] text-[#DC143C]">receipt_long</span>Riwayat Transaksi
        </a>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs" aria-label="Filter dan daftar mutasi">
        <div class="border-b border-slate-200/80 bg-slate-50/50 p-4 sm:px-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-[#DC143C] shadow-2xs"><span class="material-symbols-outlined text-[18px]">tune</span></span>
                    <div><h2 class="text-sm font-bold text-slate-900">Filter mutasi</h2><p class="text-[11px] text-slate-500">Cari berdasarkan produk, lokasi, jenis, petugas, atau tanggal</p></div>
                </div>
                <span class="text-xs text-slate-500">{{ number_format($movements->total()) }} mutasi ditemukan</span>
            </div>
            <form method="GET" action="{{ route('inventory.movements.index') }}" class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="relative block sm:col-span-2 xl:col-span-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><span class="material-symbols-outlined text-[18px]">search</span></span>
                    <input name="reference" value="{{ $filters['reference'] ?? '' }}" placeholder="Referensi atau catatan" class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-xs text-slate-800 placeholder:text-slate-400 shadow-2xs focus:border-[#DC143C] focus:outline-none">
                </label>
                <select name="product_id" class="h-10 min-w-0 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none">
                    <option value="">Semua produk</option>
                    @foreach($products as $product)<option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} · {{ $product->name }}</option>@endforeach
                </select>
                <select name="location_id" class="h-10 min-w-0 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua lokasi</option>@endunless
                    @foreach($locations as $location)<option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>@endforeach
                </select>
                <select name="type" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none">
                    <option value="">Semua jenis mutasi</option>
                    @foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>@endforeach
                </select>
                <select name="user_id" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none">
                    <option value="">Semua petugas</option>
                    @foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>@endforeach
                </select>
                <label class="block text-[10px] font-semibold text-slate-500">Dari tanggal<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-normal text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none"></label>
                <label class="block text-[10px] font-semibold text-slate-500">Sampai tanggal<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-normal text-slate-700 shadow-2xs focus:border-[#DC143C] focus:outline-none"></label>
                <div class="flex items-end gap-2">
                    <button class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-[#DC143C] px-4 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-[#b81032]"><span class="material-symbols-outlined text-[16px]">filter_alt</span>Terapkan</button>
                    @if($hasActiveFilters)<a href="{{ route('inventory.movements.index') }}" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>@endif
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 px-4 py-3 sm:px-6">
            <div class="flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700"><span class="material-symbols-outlined text-[17px]">swap_horiz</span></span><div><h2 class="text-sm font-bold text-slate-900">Daftar mutasi</h2><p class="text-[11px] text-slate-500">Menampilkan {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} dari {{ number_format($movements->total()) }}</p></div></div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">Halaman {{ $movements->currentPage() }} / {{ max(1, $movements->lastPage()) }}</span>
        </div>

        @if($movements->isEmpty())
            <div class="p-14 text-center">
                <span class="material-symbols-outlined text-[34px] text-slate-300">swap_horiz</span>
                <p class="mt-2 text-sm font-semibold text-slate-800">Tidak ada mutasi ditemukan</p>
                <p class="mt-1 text-xs text-slate-500">Coba ubah filter atau reset pencarian.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr><th class="px-4 py-3.5">Waktu</th><th class="px-4 py-3.5">Jenis / Referensi</th><th class="px-4 py-3.5">Produk</th><th class="px-4 py-3.5">Lokasi</th><th class="px-4 py-3.5 text-right">Saldo sebelum</th><th class="px-4 py-3.5 text-right">Mutasi</th><th class="px-4 py-3.5 text-right">Saldo sesudah</th><th class="px-4 py-3.5">Petugas</th><th class="px-4 py-3.5 text-right">Detail</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($movements as $movement)
                            @php
                                $isInbound = $movement->type === 'in';
                                $typeClass = $isInbound ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200';
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3.5"><div class="font-medium text-slate-900">{{ $movement->created_at->format('d M Y, H:i') }}</div><div class="mt-0.5 text-[10px] text-slate-400">{{ $movement->created_at->diffForHumans() }}</div></td>
                                <td class="px-4 py-3.5"><span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $typeClass }}">{{ $movement->transaction_type }}</span><span class="mt-1 block max-w-44 truncate font-mono text-[10px] text-slate-500" title="{{ $movement->transaction_reference }}">{{ $movement->transaction_reference ?: 'Tanpa referensi' }}</span></td>
                                <td class="px-4 py-3.5"><div class="max-w-56 truncate font-semibold text-slate-900" title="{{ $movement->product->name }}">{{ $movement->product->name }}</div><div class="mt-0.5 font-mono text-[10px] text-slate-500">{{ $movement->product->sku }}</div></td>
                                <td class="px-4 py-3.5 text-slate-700">{{ $movement->location->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-slate-700">{{ \App\Support\QuantityFormatter::format($movement->before_quantity) }} {{ $movement->product->unit->symbol }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-bold {{ $isInbound ? 'text-emerald-700' : 'text-rose-700' }}">{{ $isInbound ? '+' : '−' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }} {{ $movement->product->unit->symbol }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right font-semibold text-slate-900">{{ \App\Support\QuantityFormatter::format($movement->after_quantity) }} {{ $movement->product->unit->symbol }}</td>
                                <td class="px-4 py-3.5 text-slate-700">{{ $movement->user->name ?? 'Sistem' }}</td>
                                <td class="px-4 py-3.5 text-right"><a class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition-colors hover:border-rose-200 hover:text-[#DC143C]" href="{{ route('inventory.movements.show', $movement) }}">Buka<span class="material-symbols-outlined text-[14px]">arrow_forward</span></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($movements->hasPages())<div class="border-t border-slate-200/80 bg-slate-50/50 px-4 py-3 sm:px-6">{{ $movements->links() }}</div>@endif
    </section>
</section>
@endsection
