@extends('layouts.dashboard')

@php $pageTitle = 'Riwayat Transaksi — Technolife'; @endphp
@php
    $labels = [
        'stock_in' => 'Stock In',
        'stock_out' => 'Stock Out',
        'stock_opname' => 'Stock Opname',
        'adjustment' => 'Adjustment',
        'stock_disposal' => 'Write-off / Retur',
        'audit' => 'Aktivitas / Audit',
    ];
@endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full p-4 space-y-4">

    <!-- Mobile Header -->
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200/60 text-[10px] font-semibold text-[#DC143C] uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-[13px]">receipt_long</span>
                <span>Riwayat & Audit</span>
            </div>
            <h1 class="font-poppins text-xl font-bold text-slate-900 tracking-tight">Riwayat Transaksi</h1>
            <p class="text-xs text-slate-500 mt-0.5">Jejak transaksi dan aktivitas operasional</p>
        </div>
        <a href="{{ route('inventory.movements.index') }}"
           class="h-9 px-3 bg-[#DC143C] text-white text-xs font-semibold rounded-xl flex items-center gap-1 active:scale-95 transition-transform shrink-0 shadow-xs">
            <span class="material-symbols-outlined text-[15px]">trending_up</span>Mutasi
        </a>
    </div>

    <!-- Mobile 2x2 Mini KPIs -->
    <div class="grid grid-cols-2 gap-2.5">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total Dokumen</span>
                <span class="material-symbols-outlined text-[#DC143C] text-[18px]">receipt_long</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-slate-900">
                {{ number_format($stats['total_all'] ?? $entries->total()) }}
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Semua tipe catatan</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Stock In</span>
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">move_to_inbox</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-emerald-700">
                {{ number_format($stats['stock_in_count'] ?? 0) }}
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Penerimaan barang</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Stock Out</span>
                <span class="material-symbols-outlined text-rose-600 text-[18px]">outbox</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-rose-700">
                {{ number_format($stats['stock_out_count'] ?? 0) }}
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Pengeluaran barang</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Opname & Penyesuaian</span>
                <span class="material-symbols-outlined text-blue-600 text-[18px]">fact_check</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-blue-700">
                {{ number_format(($stats['opname_count'] ?? 0) + ($stats['adjustment_count'] ?? 0)) }}
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Pemeriksaan & koreksi</p>
        </div>
    </div>

    <!-- Type Filter Chips (Horizontal Scroll) -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
        <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ empty($filters['type']) ? 'bg-[#DC143C] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-700' }}">
            Semua
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_in', 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'stock_in' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-emerald-700' }}">
            In ({{ $stats['stock_in_count'] ?? 0 }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_out', 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'stock_out' ? 'bg-rose-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-rose-700' }}">
            Out ({{ $stats['stock_out_count'] ?? 0 }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_opname', 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'stock_opname' ? 'bg-blue-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-blue-700' }}">
            Opname ({{ $stats['opname_count'] ?? 0 }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'adjustment', 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'adjustment' ? 'bg-amber-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-amber-700' }}">
            Adjust ({{ $stats['adjustment_count'] ?? 0 }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_disposal', 'page' => null]) }}" class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'stock_disposal' ? 'bg-orange-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-orange-700' }}">Write-off / Retur ({{ $stats['disposal_count'] ?? 0 }})</a>
        @if(auth()->user()->hasPermissionTo('users.manage'))
            <a href="{{ request()->fullUrlWithQuery(['type' => 'audit', 'page' => null]) }}"
               class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap transition-colors {{ ($filters['type'] ?? '') === 'audit' ? 'bg-purple-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-purple-700' }}">
                Audit ({{ $stats['audit_count'] ?? 0 }})
            </a>
        @endif
    </div>

    <!-- Mobile Filter Accordion -->
    <details class="group rounded-2xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs">
        <summary class="flex items-center justify-between p-3.5 cursor-pointer list-none text-xs font-semibold text-slate-800">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#DC143C] text-[18px]">tune</span>
                <span>Filter Lanjutan</span>
                @if(array_filter($filters ?? []))
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                @endif
            </div>
            <span class="material-symbols-outlined text-slate-400 text-[18px] transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <form method="GET" action="{{ route('inventory.transactions.index') }}" data-auto-filter class="p-3.5 pt-0 border-t border-slate-100 space-y-2.5">
            @if(!empty($filters['type']))
                <input type="hidden" name="type" value="{{ $filters['type'] }}">
            @endif

            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </span>
                <input name="reference" value="{{ $filters['reference'] ?? '' }}" placeholder="Cari nomor referensi atau aktivitas..."
                       class="w-full pl-9 pr-3 h-10 bg-surface-container-lowest border border-outline rounded-xl text-xs text-on-surface outline-none focus:border-primary">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <select name="location_id" class="h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi</option>@endunless
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>

                <select name="user_id" class="h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                    <option value="">Semua Petugas</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <select name="product_id" class="w-full h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                <option value="">Semua Produk Terkait</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>

            <div class="grid grid-cols-2 gap-2">
                <label class="text-[10px] text-secondary">Dari Tanggal
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="mt-1 w-full h-8 border border-outline rounded-lg px-2 text-[11px] bg-surface-container-lowest">
                </label>
                <label class="text-[10px] text-secondary">Sampai Tanggal
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="mt-1 w-full h-8 border border-outline rounded-lg px-2 text-[11px] bg-surface-container-lowest">
                </label>
            </div>

            <div class="flex justify-end pt-1">
                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.transactions.index') }}" class="h-9 px-3 border border-outline text-secondary text-xs font-semibold rounded-xl flex items-center justify-center">Reset</a>
                @endif
            </div>
        </form>
    </details>

    <!-- Mobile Transaction Entries List -->
    @if($entries->isEmpty())
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center mx-auto text-secondary mb-3">
                <span class="material-symbols-outlined text-[24px]">receipt_long</span>
            </div>
            <p class="font-semibold text-on-surface text-sm">Tidak Ada Riwayat Transaksi</p>
            <p class="text-xs text-secondary mt-1">Belum ada catatan aktivitas yang sesuai dengan filter pencarian Anda.</p>
            @if(array_filter($filters ?? []))
                <a href="{{ route('inventory.transactions.index') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 border border-outline text-xs font-semibold rounded-xl">
                    Reset Filter
                </a>
            @endif
        </div>
    @else
        <div class="space-y-3">
            @foreach($entries as $entry)
                @php
                    $typeConfig = match ($entry->type) {
                        'stock_in' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'move_to_inbox', 'label' => 'Stock In'],
                        'stock_out' => ['badge' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'outbox', 'label' => 'Stock Out'],
                        'stock_opname' => ['badge' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fact_check', 'label' => 'Stock Opname'],
                        'adjustment' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'tune', 'label' => 'Adjustment'],
                        'stock_disposal' => ['badge' => 'bg-orange-50 text-orange-700 border-orange-200', 'icon' => 'delete_sweep', 'label' => 'Write-off / Retur'],
                        'audit' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'security', 'label' => 'Audit'],
                        default => ['badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'receipt', 'label' => ucfirst($entry->type)],
                    };
                @endphp
                <a href="{{ route('inventory.transactions.show', [$entry->type, $entry->id]) }}"
                   class="block rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs active:bg-slate-50 transition-colors">
                    <!-- Top Row: Type Pill & Timestamp -->
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $typeConfig['badge'] }}">
                            <span class="material-symbols-outlined text-[13px]">{{ $typeConfig['icon'] }}</span>
                            <span>{{ $typeConfig['label'] }}</span>
                        </span>
                        <span class="text-[11px] text-slate-500">
                            {{ $entry->created_at ? \Illuminate\Support\Carbon::parse($entry->created_at)->format('d M Y, H:i') : '—' }}
                        </span>
                    </div>

                    <!-- Reference Number -->
                    <div class="mt-2">
                        <p class="font-mono text-xs font-semibold text-slate-900">
                            {{ $entry->reference }}
                        </p>
                        @if($entry->description)
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                {{ $entry->description }}
                            </p>
                        @endif
                    </div>

                    <!-- Location & User Info -->
                    <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <div class="flex items-center gap-1 truncate max-w-[170px]">
                            <span class="material-symbols-outlined text-[15px] text-slate-400">location_on</span>
                            <span class="truncate">{{ $entry->location_name ?? 'Sistem / Global' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center font-semibold text-[10px] text-[#DC143C]">
                                {{ strtoupper(substr($entry->user_name ?? 'S', 0, 1)) }}
                            </span>
                            <span class="truncate max-w-[90px]">{{ $entry->user_name ?? 'System' }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if($entries->hasPages())
            <div class="pt-2">{{ $entries->links() }}</div>
        @endif
    @endif

</main>
@endsection


{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT (>= lg)                                                 --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<section class="space-y-5">

    <!-- Header Row -->
    <div class="flex items-start justify-between gap-4 min-w-0">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                <span>Manajemen Inventaris</span>
                <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
                <span class="font-semibold text-slate-800">Riwayat Transaksi</span>
            </nav>
            <h1 class="font-poppins text-2xl font-bold text-slate-900 tracking-tight">Riwayat Transaksi</h1>
            <p class="text-xs text-slate-500 mt-1 max-w-3xl">
                Arsip komprehensif seluruh transaksi stok (Stock In, Stock Out, Opname, Adjustment) dan jejak audit aktivitas sistem.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 pt-2">
            <a href="{{ route('inventory.stock.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span>Data Stock</span>
            </a>
            <a href="{{ route('inventory.movements.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#DC143C] hover:bg-[#b81032] text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">trending_up</span>
                <span>Riwayat Mutasi Stok →</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Metric 1: Total Seluruh Catatan -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Seluruh Dokumen</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center text-[#DC143C]">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-bold text-slate-900 leading-tight">
                {{ number_format($stats['total_all'] ?? $entries->total()) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                <span>Seluruh catatan log & transaksi</span>
            </div>
        </div>

        <!-- Metric 2: Stock In -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transaksi Masuk</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-700">
                    <span class="material-symbols-outlined text-[20px]">move_to_inbox</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-bold text-emerald-700 leading-tight">
                {{ number_format($stats['stock_in_count'] ?? 0) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700">
                <span class="material-symbols-outlined text-[14px]">arrow_downward</span>
                <span>Penerimaan barang vendor</span>
            </div>
        </div>

        <!-- Metric 3: Stock Out -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transaksi Keluar</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center text-rose-700">
                    <span class="material-symbols-outlined text-[20px]">outbox</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-bold text-rose-700 leading-tight">
                {{ number_format($stats['stock_out_count'] ?? 0) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-rose-700">
                <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
                <span>Pengeluaran operasional & dapur</span>
            </div>
        </div>

        <!-- Metric 4: Opname & Adjustment -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Opname & Penyesuaian</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-700">
                    <span class="material-symbols-outlined text-[20px]">fact_check</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-bold text-slate-900 leading-tight">
                {{ number_format(($stats['opname_count'] ?? 0) + ($stats['adjustment_count'] ?? 0)) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-500">
                <span>Pemeriksaan fisik & koreksi selisih</span>
            </div>
        </div>
    </div>

    <!-- Quick Type Navigation Tabs -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs scrollbar-none">
        <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}"
           class="px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ empty($filters['type']) ? 'bg-[#DC143C] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-700 hover:bg-slate-50' }}">
            Semua ({{ number_format($stats['total_all'] ?? $entries->total()) }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_in', 'page' => null]) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ ($filters['type'] ?? '') === 'stock_in' ? 'bg-emerald-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-emerald-700 hover:bg-emerald-50' }}">
            <span class="material-symbols-outlined text-[15px]">move_to_inbox</span>
            <span>Stock In ({{ number_format($stats['stock_in_count'] ?? 0) }})</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_out', 'page' => null]) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ ($filters['type'] ?? '') === 'stock_out' ? 'bg-rose-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-rose-700 hover:bg-rose-50' }}">
            <span class="material-symbols-outlined text-[15px]">outbox</span>
            <span>Stock Out ({{ number_format($stats['stock_out_count'] ?? 0) }})</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_opname', 'page' => null]) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ ($filters['type'] ?? '') === 'stock_opname' ? 'bg-blue-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-blue-700 hover:bg-blue-50' }}">
            <span class="material-symbols-outlined text-[15px]">fact_check</span>
            <span>Stock Opname ({{ number_format($stats['opname_count'] ?? 0) }})</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'adjustment', 'page' => null]) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ ($filters['type'] ?? '') === 'adjustment' ? 'bg-amber-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-amber-700 hover:bg-amber-50' }}">
            <span class="material-symbols-outlined text-[15px]">tune</span>
            <span>Adjustment ({{ number_format($stats['adjustment_count'] ?? 0) }})</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'stock_disposal', 'page' => null]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap {{ ($filters['type'] ?? '') === 'stock_disposal' ? 'bg-orange-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-orange-700 hover:bg-orange-50' }}"><span class="material-symbols-outlined text-[15px]">delete_sweep</span><span>Write-off / Retur ({{ number_format($stats['disposal_count'] ?? 0) }})</span></a>
        @if(auth()->user()->hasPermissionTo('users.manage'))
            <a href="{{ request()->fullUrlWithQuery(['type' => 'audit', 'page' => null]) }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors whitespace-nowrap {{ ($filters['type'] ?? '') === 'audit' ? 'bg-purple-700 text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-purple-700 hover:bg-purple-50' }}">
                <span class="material-symbols-outlined text-[15px]">security</span>
                <span>Audit Trail ({{ number_format($stats['audit_count'] ?? 0) }})</span>
            </a>
        @endif
    </div>

    <!-- Main Card with Filter Bar & Table -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">

        <!-- Filter Bar -->
        <div class="p-4 sm:px-6 border-b border-slate-200/80 bg-slate-50/50">
            <form method="GET" action="{{ route('inventory.transactions.index') }}" data-auto-filter class="flex flex-wrap items-center gap-3">
                @if(!empty($filters['type']))
                    <input type="hidden" name="type" value="{{ $filters['type'] }}">
                @endif

                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </div>
                    <input name="reference" value="{{ $filters['reference'] ?? '' }}"
                           placeholder="Cari no. referensi, deskripsi, atau aktivitas..."
                           class="w-full pl-9 pr-4 h-10 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all shadow-2xs">
                </div>

                <!-- Product Filter -->
                <select name="product_id" class="h-10 bg-white border border-slate-200 rounded-xl px-3 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer max-w-[240px] shadow-2xs">
                    <option value="">Semua Produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>

                <!-- Location Filter -->
                <select name="location_id" class="h-10 bg-white border border-slate-200 rounded-xl px-3 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer shadow-2xs">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi Gudang</option>@endunless
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>

                <!-- User Filter -->
                <select name="user_id" class="h-10 bg-white border border-slate-200 rounded-xl px-3 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer shadow-2xs">
                    <option value="">Semua Petugas</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>

                <!-- Date Range -->
                <div class="flex items-center gap-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-xl px-2 py-0.5 shadow-2xs">
                    <span class="text-[11px] text-slate-400">Dari:</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="text-xs bg-transparent text-slate-700 outline-none h-8">
                    <span class="text-slate-300">|</span>
                    <span class="text-[11px] text-slate-400">Sampai:</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="text-xs bg-transparent text-slate-700 outline-none h-8">
                </div>

                <!-- Action Buttons -->
                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.transactions.index') }}" class="h-10 px-3.5 bg-rose-50 hover:bg-rose-100 text-[#DC143C] text-xs font-semibold rounded-xl flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">refresh</span>Reset
                    </a>
                @endif

                <!-- Total Count Indicator -->
                <div class="ml-auto text-xs text-slate-500 font-medium">
                    Menampilkan <span class="font-semibold text-slate-800">{{ $entries->count() }}</span> dari <span class="font-semibold text-slate-800">{{ $entries->total() }}</span> catatan
                </div>
            </form>
        </div>

        <!-- Table View -->
        @if($entries->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto text-gray-400 mb-3">
                    <span class="material-symbols-outlined text-[28px]">receipt_long</span>
                </div>
                <h3 class="font-semibold text-gray-800 text-sm">Tidak Ada Transaksi Ditemukan</h3>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                    @if(array_filter($filters ?? []))
                        Tidak ada catatan riwayat yang cocok dengan kriteria filter Anda. Coba reset filter.
                    @else
                        Belum ada riwayat transaksi atau audit yang tercatat di dalam sistem.
                    @endif
                </p>
                @if(array_filter($filters ?? []))
                    <div class="mt-4">
                        <a href="{{ route('inventory.transactions.index') }}" class="px-3.5 py-1.5 border border-[#EAEAEA] bg-white text-xs font-semibold text-gray-700 rounded-lg hover:bg-gray-50">
                            Reset Filter
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-[1040px] text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 whitespace-nowrap">Waktu Transaksi</th>
                            <th class="py-3 px-4">Tipe / Jenis</th>
                            <th class="py-3 px-4">No. Referensi</th>
                            <th class="py-3 px-4">Aktivitas / Keterangan</th>
                            <th class="py-3 px-4">Lokasi Gudang</th>
                            <th class="py-3 px-4">Pelaksana</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($entries as $entry)
                            @php
                                $typeConfig = match ($entry->type) {
                                    'stock_in' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'move_to_inbox', 'label' => 'Stock In'],
                                    'stock_out' => ['badge' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'outbox', 'label' => 'Stock Out'],
                        'stock_opname' => ['badge' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fact_check', 'label' => 'Stock Opname'],
                                    'adjustment' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'tune', 'label' => 'Adjustment'],
                        'stock_disposal' => ['badge' => 'bg-orange-50 text-orange-700 border-orange-200', 'icon' => 'delete_sweep', 'label' => 'Write-off / Retur'],
                                    'audit' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'security', 'label' => 'Audit'],
                                    default => ['badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'receipt', 'label' => ucfirst($entry->type)],
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Waktu -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-700">
                                    <div class="font-medium text-slate-900">
                                        {{ $entry->created_at ? \Illuminate\Support\Carbon::parse($entry->created_at)->format('d M Y, H:i') : '—' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $entry->created_at ? \Illuminate\Support\Carbon::parse($entry->created_at)->diffForHumans() : '' }}
                                    </div>
                                </td>

                                <!-- Jenis Transaksi -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $typeConfig['badge'] }}">
                                        <span class="material-symbols-outlined text-[14px]">{{ $typeConfig['icon'] }}</span>
                                        <span>{{ $typeConfig['label'] }}</span>
                                    </span>
                                </td>

                                <!-- Referensi -->
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('inventory.transactions.show', [$entry->type, $entry->id]) }}"
                                       class="font-mono font-semibold text-slate-900 hover:text-[#DC143C] hover:underline">
                                        {{ $entry->reference }}
                                    </a>
                                </td>

                                <!-- Keterangan / Aktivitas -->
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-700 max-w-xs truncate font-medium">
                                        {{ $entry->description ?: '—' }}
                                    </div>
                                </td>

                                <!-- Lokasi -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($entry->location_name)
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium text-[11px]">
                                            <span class="material-symbols-outlined text-[14px] text-slate-500">warehouse</span>
                                            <span>{{ $entry->location_name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Sistem / Global</span>
                                    @endif
                                </td>

                                <!-- Pelaksana -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-semibold text-[10px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($entry->user_name ?? 'S', 0, 1)) }}
                                        </div>
                                        <span class="text-slate-800 font-medium">{{ $entry->user_name ?? 'System' }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ str_replace('_', ' ', ucfirst($entry->status)) }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('inventory.transactions.show', [$entry->type, $entry->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs hover:border-slate-300 transition-colors">
                                        <span>Detail</span>
                                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($entries->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $entries->links() }}
                </div>
            @endif
        @endif

    </div>

</section>
@endsection
