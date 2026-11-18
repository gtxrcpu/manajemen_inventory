@extends('layouts.dashboard')
@php
    $pageTitle = 'Laporan ' . ($definition['label'] ?? 'Inventaris');
    $reportIconMap = [
        'stock' => 'inventory_2',
        'movements' => 'swap_horiz',
        'stock_in' => 'add_circle',
        'stock_out' => 'remove_circle',
        'opname' => 'fact_check',
        'adjustment' => 'tune',
        'expiry' => 'event_busy',
        'purchasing' => 'shopping_cart',
    ];
    $hasActiveFilters = !empty($filters['location_id']) || !empty($filters['product_id']) || !empty($filters['category_id']) || !empty($filters['transaction_type']) || !empty($filters['user_id']) || !empty($filters['date_from']) || !empty($filters['date_to']);
@endphp

@once
<style>
    .report-card {
        background-color: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    }
</style>
@endonce

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-12">
    {{-- Header --}}
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/70 bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#DC143C]">
                <span class="material-symbols-outlined text-[14px]">analytics</span>
                Pusat Laporan
            </span>
            <span class="text-xs text-slate-400">/</span>
            <span class="text-xs font-medium text-slate-500">{{ $definition['label'] }}</span>
        </div>
        <h1 class="font-poppins text-xl font-bold tracking-tight text-slate-900">
            Laporan {{ $definition['label'] }}
        </h1>
        <p class="text-xs text-slate-500">
            Total {{ number_format($rows->total(), 0, ',', '.') }} baris data ditemukan
        </p>

        {{-- Quick Links for invoice / valuation permissions --}}
        <div class="flex flex-wrap items-center gap-2 pt-1">
            @if(auth()->user()->hasPermissionTo('invoice.view'))
                <a href="{{ route('purchase-invoices.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[15px] text-slate-400">receipt_long</span>
                    Faktur Pembelian
                </a>
            @endif
            @if(auth()->user()->hasRole('management') || auth()->user()->hasPermissionTo('invoice.review'))
                <a href="{{ route('finance.valuation') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[15px] text-slate-400">calculate</span>
                    Valuasi Persediaan
                </a>
            @endif
        </div>
    </div>

    {{-- Error Banner --}}
    @if($errors->any())
        <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
            <span class="material-symbols-outlined text-[18px] text-rose-600 shrink-0">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- Report Category Horizontal Pills --}}
    <div class="flex items-center gap-1.5 overflow-x-auto py-1 -mx-4 px-4 scrollbar-none">
        @foreach($reportTypes as $key => $label)
            <a href="{{ route('reports.index', array_merge($filters, ['report' => $key])) }}"
               class="inline-flex items-center gap-1.5 shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $reportType === $key ? 'bg-[#DC143C] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-700 hover:bg-slate-50' }}">
                <span class="material-symbols-outlined text-[15px] {{ $reportType === $key ? 'text-white' : 'text-slate-400' }}">
                    {{ $reportIconMap[$key] ?? 'assessment' }}
                </span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>

    {{-- Mobile Filter Form --}}
    <form method="GET" action="{{ route('reports.index') }}" data-auto-filter class="report-card p-4 space-y-3">
        <input type="hidden" name="report" value="{{ $reportType }}">

        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px] text-slate-500">tune</span>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Filter Data</span>
            </div>
            @if($hasActiveFilters)
                <a href="{{ route('reports.index', ['report' => $reportType]) }}" class="text-[11px] font-semibold text-[#DC143C] hover:underline">
                    Reset Filter
                </a>
            @endif
        </div>

        @if($definition['date'])
        <div class="grid grid-cols-2 gap-2">
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">Dari Tanggal</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border border-slate-200 p-2 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:border-[#DC143C] focus:outline-none">
            </label>
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">Sampai Tanggal</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-xl border border-slate-200 p-2 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:border-[#DC143C] focus:outline-none">
            </label>
        </div>
        @endif

        @if($picLocation)
            <input type="hidden" name="location_id" value="{{ $picLocation->id }}">
            <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-2.5 text-xs text-blue-800">
                Lokasi tugas: <strong>{{ $picLocation->name }}</strong>
            </div>
        @else
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">Lokasi</span>
                <div class="relative">
                    <select name="location_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8">
                        @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi</option>@endunless
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                </div>
            </label>
        @endif

        <div class="grid grid-cols-2 gap-2">
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">Produk</span>
                <div class="relative">
                    <select name="product_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-7">
                        <option value="">Semua Produk</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </span>
                </div>
            </label>

            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">Kategori</span>
                <div class="relative">
                    <select name="category_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-7">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </span>
                </div>
            </label>
        </div>

        @if($transactionTypes)
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1 block">{{ $reportType === 'purchasing' ? 'Status PO' : 'Jenis Transaksi' }}</span>
                <div class="relative">
                    <select name="transaction_type" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8">
                        <option value="">Semua</option>
                        @foreach($transactionTypes as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['transaction_type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                </div>
            </label>
        @endif

        {{-- Export Action Buttons --}}
        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
            <button type="button" data-report-export="xlsx" data-export-url="{{ route('reports.export', array_merge($filters, ['format' => 'xlsx'])) }}"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 flex items-center justify-center gap-1.5 shadow-2xs active:scale-[0.98] transition-transform">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">table_view</span>
                Unduh Excel
            </button>
            <button type="button" data-report-export="pdf" data-export-url="{{ route('reports.export', array_merge($filters, ['format' => 'pdf'])) }}"
                    class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800 flex items-center justify-center gap-1.5 shadow-2xs active:scale-[0.98] transition-transform">
                <span class="material-symbols-outlined text-[16px] text-rose-600">picture_as_pdf</span>
                Unduh PDF
            </button>
        </div>
    </form>

    {{-- Mobile Cards Feed --}}
    <section class="space-y-3">
        @forelse($rows as $row)
            <article class="report-card p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900 truncate">{{ $row->product ?? $row->reference ?? 'Data Laporan' }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1 text-[11px] text-slate-500">
                            @if(isset($row->sku))
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600">{{ $row->sku }}</span>
                            @endif
                            @if(isset($row->category))
                                <span>&middot; {{ $row->category }}</span>
                            @endif
                            @if(isset($row->location))
                                <span>&middot; <strong class="text-slate-700">{{ $row->location }}</strong></span>
                            @endif
                        </div>
                    </div>
                    @if(isset($row->quantity))
                        <div class="text-right shrink-0">
                            <span class="font-poppins text-base font-bold text-slate-900">
                                {{ \App\Support\QuantityFormatter::format($row->quantity) }}
                            </span>
                            <span class="block text-[10px] uppercase font-semibold text-slate-400">{{ $row->unit ?? '' }}</span>
                        </div>
                    @endif
                </div>

                {{-- Detail Attributes Grid --}}
                <dl class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-xs">
                    @foreach($columns as $key => $label)
                        @if(!in_array($key, ['product','sku','category','quantity','unit','location'], true) && isset($row->{$key}) && $row->{$key} !== '')
                            <div class="rounded-lg bg-slate-50/70 p-2 border border-slate-100">
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-slate-800 break-words">
                                    @if(in_array($key, ['date', 'expiry'], true))
                                        {{ \Illuminate\Support\Carbon::parse($row->{$key})->format('d M Y') }}
                                    @elseif(in_array($key, ['system_quantity','physical_quantity','variance','old_quantity','new_quantity','available','quarantined'], true))
                                        <span class="font-semibold text-slate-900">{{ \App\Support\QuantityFormatter::format($row->{$key}) }}</span> {{ $row->unit ?? '' }}
                                    @elseif(in_array($key, ['unit_price', 'line_total'], true))
                                        <span class="font-semibold text-slate-900">Rp {{ number_format((float) $row->{$key}, 0, ',', '.') }}</span>
                                    @elseif($key === 'status')
                                        <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {{ in_array($row->{$key}, ['approved','received','Aman','confirmed'], true) ? 'bg-emerald-50 text-emerald-700' : (in_array($row->{$key}, ['Kedaluwarsa','rejected'], true) ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ ucfirst(str_replace('_', ' ', $row->{$key})) }}
                                        </span>
                                    @else
                                        {{ $row->{$key} }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <span class="material-symbols-outlined text-[36px] text-slate-300 mb-2">bar_chart</span>
                <p class="text-sm font-semibold text-slate-700">Tidak ada data laporan</p>
                <p class="text-xs text-slate-400 mt-0.5">Coba sesuaikan filter pencarian atau rentang tanggal Anda.</p>
            </div>
        @endforelse

        {{ $rows->links() }}
    </section>
</main>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<section class="space-y-5 pb-12">

    {{-- ==================== 1. EXECUTIVE HEADER ==================== --}}
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/70 bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#DC143C]">
                    <span class="material-symbols-outlined text-[15px]">analytics</span>
                    Manajemen Inventaris
                </span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold text-slate-700">Laporan {{ $definition['label'] }}</span>
            </div>
            <h1 class="mt-2 font-poppins text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                Laporan {{ $definition['label'] }}
            </h1>
            <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                Gunakan filter untuk mempersempit data, lalu unduh hasilnya dalam format Excel atau PDF.
                <span class="inline-block font-medium text-slate-500">
                    {{ number_format($rows->total(), 0, ',', '.') }} baris ditemukan
                </span>
            </p>
        </div>

        {{-- Executive Action Group (Invoice & Valuation quick links) --}}
        <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
            @if(auth()->user()->hasPermissionTo('invoice.view'))
                <a href="{{ route('purchase-invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-[16px] text-slate-400">receipt_long</span>
                    Faktur Pembelian
                </a>
            @endif
            @if(auth()->user()->hasRole('management') || auth()->user()->hasPermissionTo('invoice.review'))
                <a href="{{ route('finance.valuation') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-[16px] text-slate-400">calculate</span>
                    Valuasi Persediaan
                </a>
            @endif
        </div>
    </header>

    {{-- Error Banner --}}
    @if($errors->any())
        <div role="alert" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <span class="material-symbols-outlined text-[20px] text-rose-600 shrink-0 mt-0.5">error</span>
            <div>
                <p class="font-semibold">Terjadi kendala pada filter:</p>
                <p class="mt-0.5 text-xs text-rose-700">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    {{-- ==================== 2. REPORT TYPE TABS ==================== --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        @foreach($reportTypes as $key => $label)
            <a href="{{ route('reports.index', array_merge($filters, ['report' => $key])) }}"
               class="inline-flex items-center gap-2 shrink-0 rounded-lg px-3.5 py-2 text-xs font-semibold transition-colors {{ $reportType === $key ? 'bg-[#DC143C] text-white shadow-2xs' : 'bg-white border border-slate-200/80 text-slate-700 hover:bg-slate-50' }}">
                <span class="material-symbols-outlined text-[17px] {{ $reportType === $key ? 'text-white' : 'text-slate-400' }}">
                    {{ $reportIconMap[$key] ?? 'assessment' }}
                </span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </div>

    {{-- ==================== 3. FILTER TOOLBAR ==================== --}}
    <form method="GET" action="{{ route('reports.index') }}" data-auto-filter class="report-card p-5 sm:p-6 space-y-4">
        <input type="hidden" name="report" value="{{ $reportType }}">

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">tune</span>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Kriteria Filter</h2>
                    <p class="text-xs text-slate-500">Hasil diperbarui saat filter diubah</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($hasActiveFilters)
                    <a href="{{ route('reports.index', ['report' => $reportType]) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                        Reset Filter
                    </a>
                @endif
                {{-- Export Buttons --}}
                <button type="button" data-report-export="xlsx" data-export-url="{{ route('reports.export', array_merge($filters, ['format' => 'xlsx'])) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 shadow-2xs transition-colors">
                    <span class="material-symbols-outlined text-[16px] text-emerald-600">table_view</span>
                    Excel
                </button>
                <button type="button" data-report-export="pdf" data-export-url="{{ route('reports.export', array_merge($filters, ['format' => 'pdf'])) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-1.5 text-xs font-semibold text-rose-800 hover:bg-rose-100 shadow-2xs transition-colors">
                    <span class="material-symbols-outlined text-[16px] text-rose-600">picture_as_pdf</span>
                    PDF
                </button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
            {{-- Date filters --}}
            @if($definition['date'])
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Dari Tanggal</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none transition-all">
                </label>
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Sampai Tanggal</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none transition-all">
                </label>
            @endif

            {{-- Location filter --}}
            @if($picLocation)
                <input type="hidden" name="location_id" value="{{ $picLocation->id }}">
                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-3 text-xs text-blue-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-blue-600">location_on</span>
                    <span>Lokasi: <strong>{{ $picLocation->name }}</strong></span>
                </div>
            @else
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Lokasi Gudang / Outlet</span>
                    <div class="relative">
                        <select name="location_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8 transition-all">
                            @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi</option>@endunless
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </span>
                    </div>
                </label>
            @endif

            {{-- Product filter --}}
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Produk</span>
                <div class="relative">
                    <select name="product_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8 transition-all">
                        <option value="">Semua Produk</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                </div>
            </label>

            {{-- Category filter --}}
            <label class="block">
                <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Kategori</span>
                <div class="relative">
                    <select name="category_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8 transition-all">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                </div>
            </label>

            {{-- Transaction Type filter --}}
            @if($transactionTypes)
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">{{ $reportType === 'purchasing' ? 'Status PO' : 'Jenis Transaksi' }}</span>
                    <div class="relative">
                        <select name="transaction_type" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8 transition-all">
                            <option value="">Semua Status / Jenis</option>
                            @foreach($transactionTypes as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['transaction_type'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </span>
                    </div>
                </label>
            @endif

            {{-- User filter --}}
            @if($definition['date'])
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-600 mb-1.5 block">Petugas / Operator</span>
                    <div class="relative">
                        <select name="user_id" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-medium text-slate-800 focus:bg-white focus:border-[#DC143C] focus:outline-none pr-8 transition-all">
                            <option value="">Semua Petugas</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </span>
                    </div>
                </label>
            @endif
        </div>
    </form>

    {{-- ==================== 4. EXECUTIVE REPORT DATA TABLE ==================== --}}
    <section class="space-y-4">
        {{-- Table Container --}}
        <div class="report-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <span class="material-symbols-outlined text-[16px]">{{ $reportIconMap[$reportType] ?? 'table_chart' }}</span>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tabel Rincian {{ $definition['label'] }}</h2>
                        <p class="text-xs text-slate-500">Menampilkan {{ $rows->count() }} dari {{ number_format($rows->total(), 0, ',', '.') }} baris rekaman</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        Halaman {{ $rows->currentPage() }} dari {{ max(1, $rows->lastPage()) }}
                    </span>
                </div>
            </div>

            {{-- High-density Responsive Data Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-200/90 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="p-3.5 sm:px-4 text-center w-12">#</th>
                            @foreach($columns as $colKey => $colLabel)
                                @php
                                    $isNumeric = in_array($colKey, ['quantity','quarantined','available','system_quantity','physical_quantity','variance','old_quantity','new_quantity','unit_price','line_total','age_days'], true);
                                    $isCenter = in_array($colKey, ['status', 'type', 'direction', 'unit', 'date', 'expiry'], true);
                                @endphp
                                <th class="p-3.5 sm:px-4 {{ $isNumeric ? 'text-right' : ($isCenter ? 'text-center' : 'text-left') }} whitespace-nowrap">
                                    {{ $colLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($rows as $idx => $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="p-3.5 sm:px-4 text-center text-slate-400 font-mono text-[11px]">
                                    {{ ($rows->currentPage() - 1) * $rows->perPage() + $idx + 1 }}
                                </td>
                                @foreach($columns as $colKey => $colLabel)
                                    @php
                                        $val = $row->{$colKey} ?? null;
                                        $isNumeric = in_array($colKey, ['quantity','quarantined','available','system_quantity','physical_quantity','variance','old_quantity','new_quantity','unit_price','line_total','age_days'], true);
                                        $isCenter = in_array($colKey, ['status', 'type', 'direction', 'unit', 'date', 'expiry'], true);
                                    @endphp
                                    <td class="p-3.5 sm:px-4 {{ $isNumeric ? 'text-right' : ($isCenter ? 'text-center' : 'text-left') }} whitespace-nowrap">
                                        @if(in_array($colKey, ['date', 'expiry'], true))
                                            <span class="text-slate-600 font-medium">
                                                {{ $val ? \Illuminate\Support\Carbon::parse($val)->format('d M Y') : '—' }}
                                            </span>
                                        @elseif(in_array($colKey, ['quantity','quarantined','available','system_quantity','physical_quantity','variance','old_quantity','new_quantity'], true))
                                            <span class="font-poppins font-semibold text-slate-900">
                                                {{ \App\Support\QuantityFormatter::format($val) }}
                                            </span>
                                        @elseif(in_array($colKey, ['unit_price', 'line_total'], true))
                                            <span class="font-poppins font-semibold text-slate-900">
                                                Rp {{ number_format((float) $val, 0, ',', '.') }}
                                            </span>
                                        @elseif($colKey === 'status')
                                            @php
                                                $statusStr = (string) $val;
                                                $badgeClass = match(strtolower($statusStr)) {
                                                    'approved', 'received', 'aman', 'confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                                    'kedaluwarsa', 'rejected' => 'bg-rose-50 text-rose-700 border-rose-200/60',
                                                    'segera kedaluwarsa', 'provisional', 'pending_approval' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                                };
                                            @endphp
                                            <span class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-semibold border {{ $badgeClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $statusStr)) }}
                                            </span>
                                        @elseif($colKey === 'sku')
                                            <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-medium text-slate-700">
                                                {{ $val }}
                                            </span>
                                        @elseif($colKey === 'product')
                                            <span class="font-semibold text-slate-900">
                                                {{ $val }}
                                            </span>
                                        @elseif($colKey === 'reference')
                                            <span class="font-mono text-slate-800 font-medium">
                                                {{ $val }}
                                            </span>
                                        @else
                                            <span class="text-slate-800">
                                                {{ $val ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}" class="p-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                            <span class="material-symbols-outlined text-[28px]">search_off</span>
                                        </div>
                                        <p class="font-semibold text-slate-800 text-sm">Tidak ada baris data</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Belum ada rekaman yang sesuai dengan kombinasi filter laporan yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table Footer Pagination --}}
            <div class="border-t border-slate-100 p-4 sm:px-6">
                {{ $rows->links() }}
            </div>
        </div>
    </section>
</section>
@endsection
