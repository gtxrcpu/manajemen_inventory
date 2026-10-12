@extends('layouts.dashboard')

@php $pageTitle = 'Riwayat Stock Out — Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full p-4 space-y-4">
    <!-- Mobile Header -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-primary uppercase tracking-wider">
                <span class="material-symbols-outlined text-[14px]">outbox</span>
                <span>Outbound Stock</span>
            </div>
            <h1 class="font-poppins text-xl font-semibold text-on-surface">Pengeluaran Barang</h1>
            <p class="text-xs text-secondary mt-0.5">{{ $issues->total() }} transaksi barang keluar tercatat</p>
        </div>
        <a href="{{ route('inventory.stock-out.create') }}"
           class="h-9 px-3.5 bg-primary text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 shadow-sm active:scale-95 transition-transform shrink-0">
            <span class="material-symbols-outlined text-[16px]">add</span>Catat
        </a>
    </div>

    <!-- Alert Status -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 flex items-start gap-2.5 text-xs text-emerald-800">
            <span class="material-symbols-outlined text-emerald-600 text-[18px] shrink-0 mt-0.5">check_circle</span>
            <p class="font-medium leading-relaxed">{{ session('status') }}</p>
        </div>
    @endif

    <!-- Mobile Mini KPI Cards (2x2 Grid) -->
    <div class="grid grid-cols-2 gap-2.5">
        <div class="bg-white border border-[#EAEAEA] rounded-2xl p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total Dokumen</span>
                <span class="material-symbols-outlined text-primary text-[18px]">receipt_long</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-on-surface">
                {{ number_format($stats['total_issues'] ?? $issues->total()) }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Pengeluaran tercatat</p>
        </div>

        <div class="bg-white border border-[#EAEAEA] rounded-2xl p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total Kuantiti</span>
                <span class="material-symbols-outlined text-rose-600 text-[18px]">outbox</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-rose-700 truncate">
                {{ \App\Support\QuantityFormatter::format($stats['total_items_qty'] ?? 0) }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Unit barang keluar</p>
        </div>

        <div class="bg-white border border-[#EAEAEA] rounded-2xl p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Bulan Ini</span>
                <span class="material-symbols-outlined text-blue-600 text-[18px]">calendar_month</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-on-surface">
                {{ $stats['this_month_count'] ?? 0 }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">{{ now()->translatedFormat('F Y') }}</p>
        </div>

        <div class="bg-white border border-[#EAEAEA] rounded-2xl p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Gudang Asal</span>
                <span class="material-symbols-outlined text-purple-600 text-[18px]">warehouse</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-on-surface">
                {{ $stats['locations_count'] ?? 0 }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Lokasi terdaftar</p>
        </div>
    </div>

    <!-- Mobile Filter Accordion / Form -->
    <details class="group rounded-2xl border border-outline bg-white overflow-hidden shadow-2xs">
        <summary class="flex items-center justify-between p-3.5 cursor-pointer list-none text-xs font-semibold text-on-surface">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">tune</span>
                <span>Filter & Pencarian</span>
                @if(array_filter($filters ?? []))
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                @endif
            </div>
            <span class="material-symbols-outlined text-secondary text-[18px] transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <form method="GET" class="p-3.5 pt-0 border-t border-outline/50 space-y-2.5">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </span>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="No. referensi, tujuan, produk..."
                       class="w-full pl-9 pr-3 h-10 bg-surface-container-lowest border border-outline rounded-xl text-xs text-on-surface outline-none focus:border-primary">
            </div>

            <div>
                <select name="location_id" class="w-full h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                    <option value="">Semua Lokasi Asal</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filters['location_id'] ?? '') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

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

            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 h-9 bg-primary text-white text-xs font-semibold rounded-xl">Terapkan</button>
                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.stock-out.index') }}" class="h-9 px-3 border border-outline text-secondary text-xs font-semibold rounded-xl flex items-center justify-center">Reset</a>
                @endif
            </div>
        </form>
    </details>

    <!-- Mobile Issues List -->
    @if($issues->isEmpty())
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center mx-auto text-secondary mb-3">
                <span class="material-symbols-outlined text-[24px]">outbox</span>
            </div>
            <p class="font-semibold text-on-surface text-sm">Belum Ada Pengeluaran Barang</p>
            <p class="text-xs text-secondary mt-1">Data pengeluaran akan muncul setelah transaksi Stock Out dicatat.</p>
            <a href="{{ route('inventory.stock-out.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-semibold rounded-xl">
                <span class="material-symbols-outlined text-[16px]">add</span>Catat Sekarang
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($issues as $issue)
                @php
                    $totalQty = $issue->items->sum('quantity');
                    $productSample = $issue->items->take(2);
                @endphp
                <a href="{{ route('inventory.stock-out.show', $issue) }}"
                   class="block rounded-2xl border border-outline bg-white p-4 shadow-2xs active:bg-surface-container-low transition-colors">
                    <!-- Top Row: Ref & Status -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md inline-block">
                                {{ $issue->reference }}
                            </span>
                            <div class="flex items-center gap-1.5 text-[11px] text-secondary mt-1">
                                <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                <span>{{ $issue->issued_at->format('d M Y') }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                            Tercatat
                        </span>
                    </div>

                    <!-- Meta: Destination & Location -->
                    <div class="mt-2.5 pt-2.5 border-t border-outline/50 flex flex-wrap items-center gap-2 text-xs">
                        <div class="flex items-center gap-1 text-on-surface font-medium">
                            <span class="material-symbols-outlined text-rose-600 text-[15px]">near_me</span>
                            <span class="truncate max-w-[140px]">Tujuan: {{ $issue->destination }}</span>
                        </div>
                        <span class="text-secondary/40">•</span>
                        <div class="flex items-center gap-1 text-secondary">
                            <span class="material-symbols-outlined text-secondary text-[15px]">warehouse</span>
                            <span class="truncate max-w-[120px]">{{ $issue->location->name }}</span>
                        </div>
                    </div>

                    <!-- Items Preview Chips -->
                    <div class="mt-2 bg-surface-container-low rounded-xl p-2.5 text-xs space-y-1">
                        <div class="flex items-center justify-between text-[11px] text-secondary font-medium">
                            <span>{{ $issue->items->count() }} Jenis Produk</span>
                            <span class="font-semibold text-rose-600">Total: {{ \App\Support\QuantityFormatter::format($totalQty) }} unit</span>
                        </div>
                        <div class="flex flex-wrap gap-1 pt-0.5">
                            @foreach($productSample as $item)
                                <span class="bg-white border border-outline px-2 py-0.5 rounded-md text-[10px] text-on-surface truncate max-w-[160px]">
                                    {{ $item->product->name }}: <b>{{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}</b>
                                </span>
                            @endforeach
                            @if($issue->items->count() > 2)
                                <span class="bg-white border border-outline px-1.5 py-0.5 rounded-md text-[10px] text-secondary font-medium">
                                    +{{ $issue->items->count() - 2 }} lainnya
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Footer: Issuer -->
                    <div class="mt-2.5 flex items-center justify-between text-[11px] text-secondary">
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-surface-container flex items-center justify-center font-semibold text-[10px] text-rose-600">
                                {{ strtoupper(substr($issue->issuer->name ?? 'U', 0, 1)) }}
                            </span>
                            <span>Petugas: {{ $issue->issuer->name }}</span>
                        </div>
                        <span class="material-symbols-outlined text-secondary text-[16px]">chevron_right</span>
                    </div>
                </a>
            @endforeach
        </div>

        @if($issues->hasPages())
            <div class="pt-2">{{ $issues->links() }}</div>
        @endif
    @endif
</main>
@endsection


{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT (>= lg)                                                 --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<section class="space-y-6">

    <!-- Header Row -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#DC143C] uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-[16px]">outbox</span>
                <span>Manajemen Pengeluaran Stok</span>
            </div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">Pengeluaran Barang (Stock Out)</h1>
            <p class="text-xs text-gray-500 mt-1">
                Catatan dan riwayat barang keluar untuk operasional harian, dapur, konsumsi outlet, atau pemindahan barang.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('inventory.stock.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-xl shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span>Lihat Data Stock</span>
            </a>
            <a href="{{ route('inventory.stock-out.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] hover:bg-[#b1002c] text-white text-xs font-semibold rounded-xl shadow-xs transition-all active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Catat Stock Out Baru</span>
            </a>
        </div>
    </div>

    <!-- Alert Status -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center justify-between gap-3 text-sm text-emerald-900 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-[20px] shrink-0">check_circle</span>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <!-- 4 KPI Metrics Card Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Metric 1: Total Dokumen -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Dokumen</span>
                <div class="w-9 h-9 rounded-xl bg-[#FFF1F0] flex items-center justify-center text-[#DC143C]">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-gray-900 leading-tight">
                {{ number_format($stats['total_issues'] ?? $issues->total()) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                <span>Dokumen pengeluaran terdaftar</span>
            </div>
        </div>

        <!-- Metric 2: Total Kuantiti Keluar -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Volume Keluar</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                    <span class="material-symbols-outlined text-[20px]">outbox</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-rose-700 leading-tight truncate">
                {{ \App\Support\QuantityFormatter::format($stats['total_items_qty'] ?? 0) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-rose-600">
                <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
                <span>Akumulasi barang yang dikeluarkan</span>
            </div>
        </div>

        <!-- Metric 3: Bulan Ini -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Bulan Ini</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-gray-900 leading-tight">
                {{ $stats['this_month_count'] ?? 0 }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                <span>Pengeluaran per {{ now()->translatedFormat('F Y') }}</span>
            </div>
        </div>

        <!-- Metric 4: Lokasi Gudang Asal -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Gudang Asal</span>
                <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                    <span class="material-symbols-outlined text-[20px]">warehouse</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-gray-900 leading-tight">
                {{ $stats['locations_count'] ?? 0 }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                <span>Titik lokasi gudang operasional</span>
            </div>
        </div>
    </div>

    <!-- Main Table Card with Toolbar -->
    <div class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.04)] overflow-hidden">

        <!-- Filter Bar -->
        <div class="p-4 border-b border-[#EAEAEA] bg-[#FDFCFB]">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </div>
                    <input name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Cari no. referensi, tujuan, nama produk, SKU..."
                           class="w-full pl-9 pr-4 h-9 bg-white border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all">
                </div>

                <!-- Location Filter -->
                <select name="location_id" class="h-9 bg-white border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    <option value="">Semua Lokasi Gudang Asal</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filters['location_id'] ?? '') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>

                <!-- Date Range -->
                <div class="flex items-center gap-1.5 text-xs text-gray-500 bg-white border border-[#EAEAEA] rounded-lg px-2 py-0.5">
                    <span class="text-[11px] text-gray-400">Dari:</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="text-xs bg-transparent text-gray-700 outline-none h-7">
                    <span class="text-gray-300">|</span>
                    <span class="text-[11px] text-gray-400">Sampai:</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="text-xs bg-transparent text-gray-700 outline-none h-7">
                </div>

                <!-- Action Buttons -->
                <button type="submit" class="h-9 px-4 bg-[#DC143C] hover:bg-[#b1002c] text-white text-xs font-semibold rounded-lg shadow-2xs transition-colors shrink-0">
                    Filter
                </button>

                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.stock-out.index') }}" class="h-9 px-3 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-600 text-xs font-medium rounded-lg flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">close</span>Reset
                    </a>
                @endif

                <!-- Total Count -->
                <div class="ml-auto text-xs text-gray-500 font-medium">
                    Menampilkan <span class="font-semibold text-gray-800">{{ $issues->count() }}</span> dari <span class="font-semibold text-gray-800">{{ $issues->total() }}</span> data
                </div>
            </form>
        </div>

        <!-- Table View -->
        @if($issues->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto text-gray-400 mb-3">
                    <span class="material-symbols-outlined text-[28px]">outbox</span>
                </div>
                <h3 class="font-semibold text-gray-800 text-sm">Tidak Ada Transaksi Pengeluaran</h3>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                    @if(array_filter($filters ?? []))
                        Tidak ada data pengeluaran yang sesuai dengan kriteria filter pencarian Anda. Coba reset filter.
                    @else
                        Belum ada dokumen pengeluaran barang keluar yang dicatat di sistem.
                    @endif
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    @if(array_filter($filters ?? []))
                        <a href="{{ route('inventory.stock-out.index') }}" class="px-3.5 py-1.5 border border-[#EAEAEA] bg-white text-xs font-semibold text-gray-700 rounded-lg hover:bg-gray-50">
                            Reset Filter
                        </a>
                    @endif
                    <a href="{{ route('inventory.stock-out.create') }}" class="px-3.5 py-1.5 bg-[#DC143C] text-white text-xs font-semibold rounded-lg hover:bg-[#b1002c]">
                        + Catat Stock Out
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-b border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">No. Referensi</th>
                            <th class="py-3 px-4">Tanggal Keluar</th>
                            <th class="py-3 px-4">Tujuan Pengeluaran</th>
                            <th class="py-3 px-4">Lokasi Asal</th>
                            <th class="py-3 px-4">Rincian Barang</th>
                            <th class="py-3 px-4">Petugas</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA]">
                        @foreach($issues as $issue)
                            @php
                                $totalQuantity = $issue->items->sum('quantity');
                                $itemsCount = $issue->items->count();
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <!-- No. Referensi -->
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('inventory.stock-out.show', $issue) }}"
                                       class="font-mono font-semibold text-rose-700 hover:underline flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-rose-600">receipt</span>
                                        <span>{{ $issue->reference }}</span>
                                    </a>
                                    @if($issue->notes)
                                        <p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1 italic max-w-xs">
                                            "{{ $issue->notes }}"
                                        </p>
                                    @endif
                                </td>

                                <!-- Tanggal Keluar -->
                                <td class="py-3.5 px-4 text-gray-700 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $issue->issued_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $issue->issued_at->diffForHumans() }}</div>
                                </td>

                                <!-- Tujuan Pengeluaran -->
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 font-semibold text-xs">
                                        <span class="material-symbols-outlined text-[15px] text-rose-600">near_me</span>
                                        <span class="truncate max-w-[180px]">{{ $issue->destination }}</span>
                                    </div>
                                </td>

                                <!-- Lokasi Asal -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-medium text-[11px]">
                                        <span class="material-symbols-outlined text-[14px] text-gray-500">warehouse</span>
                                        <span>{{ $issue->location->name }}</span>
                                    </div>
                                </td>

                                <!-- Rincian Barang -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-semibold text-[11px]">
                                            {{ $itemsCount }} Produk
                                        </span>
                                        <span class="font-semibold text-gray-900 text-xs">
                                            {{ \App\Support\QuantityFormatter::format($totalQuantity) }} unit
                                        </span>
                                    </div>
                                    <!-- Items preview list -->
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($issue->items->take(2) as $item)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-50 border border-gray-200 text-[10px] text-gray-600 truncate max-w-[200px]">
                                                <span>{{ $item->product->name }}</span>
                                                <b class="text-gray-900">({{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }})</b>
                                                @if($item->batch?->batch_number)
                                                    <span class="text-[9px] text-gray-400 font-mono">#{{ $item->batch->batch_number }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                        @if($itemsCount > 2)
                                            <span class="px-1 py-0.5 rounded bg-gray-100 text-[10px] text-gray-500 font-medium">
                                                +{{ $itemsCount - 2 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Petugas -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 font-semibold text-[10px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($issue->issuer->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <span class="text-gray-800 font-medium">{{ $issue->issuer->name }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200 text-[11px] font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                        Tercatat
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('inventory.stock-out.show', $issue) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs hover:border-gray-300 transition-colors">
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
            @if($issues->hasPages())
                <div class="p-4 border-t border-[#EAEAEA] bg-[#FDFCFB]">
                    {{ $issues->links() }}
                </div>
            @endif
        @endif

    </div>

</section>
@endsection
