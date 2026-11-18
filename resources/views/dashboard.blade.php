@extends('layouts.dashboard')

@php
    $pageTitle = 'Technolife - Inventory Management Dashboard';
    $isFinance = auth()->user()->hasRole('finance');
    $isPurchasing = auth()->user()->hasRole('purchasing');
    $isWarehouse = auth()->user()->hasRole('staff_gudang');
@endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- 1. Hero Overview Card -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 shadow-xs border border-outline flex flex-col gap-3.5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-poppins text-[20px] font-semibold text-on-surface leading-tight">
                    Halo, {{ auth()->user()->name }}
                </h1>
                <p class="text-[13px] text-secondary mt-0.5">
                    {{ $isSuperAdmin ? 'Administrasi Sistem' : ($isFinance ? 'Ringkasan Keuangan & Persediaan' : 'Ringkasan gudang hari ini') }}
                    <span class="font-semibold text-secondary">{{ $activeLocationCount }} lokasi · {{ $activeProductCount }} produk aktif</span>
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[22px]">{{ $isFinance ? 'calculate' : 'inventory' }}</span>
            </div>
        </div>
        <div class="w-full flex flex-col gap-1.5">
            <div class="w-full h-2.5 rounded-full bg-surface-container overflow-hidden flex gap-0.5">
                @if($inventorySummaryAvailable)
                    <div class="h-full bg-tertiary rounded-full" style="width: 100%"></div>
                @endif
            </div>
            <div class="flex items-center justify-between pt-0.5 text-[11px] font-semibold">
                <span class="text-secondary">{{ $inventorySummaryAvailable ? 'Ringkasan stok tersedia' : 'Saldo stok belum dicatat' }}</span>
            </div>
        </div>
    </div>

    <!-- 2. Quick Actions (Mobile) -->
    @if($dashboardActions->isNotEmpty())
    <div class="grid grid-cols-2 gap-3 w-full">
        @foreach($dashboardActions as $index => $action)
            @php
                $targetUrl = $action['url'] ?? match($action['label']) {
                    'Valuasi Persediaan' => route('finance.valuation'),
                    'Faktur Pembelian' => route('purchase-invoices.index'),
                    'Stok Seluruh Gudang' => route('reports.index', ['report' => 'stock']),
                    'Pantau Gudang' => route('inventory.stock.index'),
                    'Review Opname' => route('inventory.stock-opname.index'),
                    'Validasi Penerimaan' => route('inventory.stock-in.purchase-orders'),
                    'Barang Keluar' => route('inventory.stock-out.create'),
                    'Terima Transfer', 'Terima Barang' => route('inventory.transfers.index'),
                    'Stok Outlet' => route('inventory.stock.index'),
                    default => null,
                };
            @endphp
            @if($targetUrl)
                <a href="{{ $targetUrl }}" class="h-12 {{ $index === 0 ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-primary border-2 border-primary' }} text-[13px] font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] transition-transform">
            @else
                <button type="button" onclick="openQuickActionModal(@js($action['label']))" class="h-12 {{ $index === 0 ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-primary border-2 border-primary' }} text-[13px] font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] transition-transform cursor-pointer">
            @endif
                <span class="material-symbols-outlined text-[18px]">{{ $action['icon'] }}</span>
                <span>{{ $action['label'] }}</span>
            @if($targetUrl)</a>@else</button>@endif
        @endforeach
    </div>
    @endif

    <!-- 3. Alert Summary Cards -->
    <div class="grid grid-cols-2 gap-3 w-full">
        <a href="{{ $canViewLowStock ? route('low-stock.index') : '#' }}" class="bg-primary-container p-3.5 rounded-2xl flex flex-col justify-between shadow-xs relative active:opacity-90 transition-opacity">
            <div>
                <div class="flex items-center justify-between">
                    <div class="w-8 h-8 rounded-full bg-error-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[18px]">error</span>
                    </div>
                    <span class="material-symbols-outlined text-primary text-[18px]">chevron_right</span>
                </div>
                <span class="inline-block mt-3 text-[11px] font-semibold tracking-wider text-primary uppercase">STOK MAU HABIS</span>
                <div data-low-stock-alert-count class="font-poppins text-lg font-semibold text-primary mt-0.5">{{ $canViewLowStock ? $lowStockAlertCount.' Alert' : '—' }}</div>
            </div>
            <p class="text-[11px] text-on-surface-variant mt-1 line-clamp-1">{{ $canViewLowStock ? 'Produk di bawah batas minimum' : 'Tidak tersedia untuk role ini' }}</p>
        </a>
        <div class="bg-surface-container-high p-3.5 rounded-2xl flex flex-col justify-between shadow-xs relative active:opacity-90 transition-opacity">
            <div>
                <div class="flex items-center justify-between">
                    <div class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center text-warning">
                        <span class="material-symbols-outlined text-[18px]">schedule</span>
                    </div>
                    <span class="material-symbols-outlined text-warning text-[18px]">chevron_right</span>
                </div>
                <span class="inline-block mt-3 text-[11px] font-semibold tracking-wider text-warning uppercase">MAU KADALUARSA</span>
                <div class="font-poppins text-lg font-semibold text-warning mt-0.5">{{ $inventorySummaryAvailable ? '0 Bahan' : '—' }}</div>
            </div>
            <p class="text-[11px] text-secondary mt-1 line-clamp-1">{{ $inventorySummaryAvailable ? 'Periksa tanggal kedaluwarsa' : 'Data batch belum tersedia' }}</p>
        </div>
    </div>

    <!-- 4. Quick Action Cards (Mobile) -->
    <div class="flex flex-col gap-3 w-full">
        @if(auth()->user()->hasPermissionTo('opname.manage') || auth()->user()->hasPermissionTo('opname.approve'))
        <a href="{{ route('inventory.stock-opname.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 active:scale-[0.98] transition-transform">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[22px]">inventory_2</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="font-poppins text-sm font-semibold text-gray-900">Stock Opname</h3>
                        <span class="text-[9px] font-medium text-gray-500 uppercase tracking-wider shrink-0">Verifikasi</span>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed mb-2">Cocokkan stok fisik dengan sistem untuk cegah selisih.</p>
                    <div class="flex items-center gap-1 text-gray-900 text-xs font-semibold">
                        <span>Mulai Verifikasi</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </div>
                </div>
            </div>
        </a>
        @endif

        @if(auth()->user()->hasPermissionTo('adjust.manage'))
        <a href="{{ route('inventory.stock-adjustments.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 active:scale-[0.98] transition-transform">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[22px]">tune</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="font-poppins text-sm font-semibold text-gray-900">Stock Adjustment</h3>
                        <span class="text-[9px] font-medium text-gray-500 uppercase tracking-wider shrink-0">Koreksi</span>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed mb-2">Perbaiki saldo karena kerusakan, expired, atau selisih audit.</p>
                    <div class="flex items-center gap-1 text-gray-900 text-xs font-semibold">
                        <span>Buat Adjustment</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </div>
                </div>
            </div>
        </a>
        @endif

        <a href="{{ route('inventory.movements.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 active:scale-[0.98] transition-transform">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[22px]">history</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="font-poppins text-sm font-semibold text-gray-900">Riwayat Mutasi</h3>
                        <span class="text-[9px] font-medium text-gray-500 uppercase tracking-wider shrink-0">Timeline</span>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed mb-2">Lacak semua pergerakan barang dari pertama kali masuk.</p>
                    <div class="flex items-center gap-1 text-gray-900 text-xs font-semibold">
                        <span>Lihat Timeline</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- 5. Live Stock Section (Mobile) -->
    <section class="rounded-2xl border border-outline bg-white p-4">
        <div class="flex items-center justify-between"><h2 class="font-poppins font-semibold">Saldo stok aktual</h2><span class="text-[11px] text-secondary">Pembaruan otomatis</span></div>
        <div id="mobile-live-stock" class="mt-3 space-y-2">
            @forelse($stockItems as $stock)
                <div class="flex items-center gap-3 rounded-xl bg-surface-container-lowest p-2.5">
                    <img src="{{ $stock->image_path ? asset('storage/'.$stock->image_path) : asset('images/product-placeholder.svg') }}" class="h-11 w-11 rounded-lg object-cover" alt="">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $stock->name }}</p>
                        <p class="text-xs text-secondary">{{ $stock->sku }} · {{ $stock->category->name }}</p>
                    </div>
                    <b class="text-sm">{{ \App\Support\QuantityFormatter::format($stock->current_stock) }} {{ $stock->unit->symbol }}</b>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-secondary">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p>
            @endforelse
        </div>
        @if(auth()->user()->hasPermissionTo('stock.manage'))
        <div class="mt-3 flex flex-wrap gap-2">
            @if($isWarehouse)
            <a class="inline-flex rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white active:scale-95 transition-transform" href="{{ route('inventory.stock-in.purchase-orders') }}">Validasi Penerimaan PO</a>
            @else
            <a class="inline-flex rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white active:scale-95 transition-transform" href="{{ route('inventory.stock-in.create') }}">Catat Stock In</a>
            @endif
            <a class="inline-flex rounded-xl border border-primary px-4 py-2 text-sm font-semibold text-primary active:scale-95 transition-transform" href="{{ route('inventory.stock-out.create') }}">Catat Stock Out</a>
        </div>
        @endif
    </section>

</div>
@endsection

{{-- ======================================================================= --}}
{{-- NAVBAR SEARCH HANDLER (desktop)                                          --}}
{{-- ======================================================================= --}}
@section('navbar-search-handler')oninput="filterDesktopTable(this.value)"@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')

    {{-- 1. Executive Header --}}
    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between border-b border-slate-200/80 pb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-slate-700">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isFinance ? 'bg-[#DC143C]' : 'bg-emerald-500' }} animate-pulse"></span>
                    {{ $isFinance ? 'Finance & Valuation Suite' : ($isSuperAdmin ? 'Administrasi Sistem' : 'Operasional Persediaan') }}
                </span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-medium text-slate-500">Dashboard Utama</span>
            </div>
            <h1 class="mt-1.5 font-poppins text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                Halo, {{ auth()->user()->name }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                <span class="font-medium text-slate-700">{{ $activeLocationCount }} lokasi</span> &middot;
                <span class="font-medium text-slate-700">{{ $activeProductCount }} produk aktif</span>
                <span class="text-slate-400">({{ $inventorySummaryAvailable ? 'Ringkasan stok tersedia' : 'Saldo stok belum dicatat' }})</span>
            </p>
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-medium shadow-2xs">
                <span class="material-symbols-outlined text-[16px] text-slate-400">calendar_today</span>
                {{ now()->locale('id')->translatedFormat('l, d M Y') }}
            </span>
        </div>
    </header>

    {{-- 2. Quick Actions Grid (Desktop) --}}
    @if($dashboardActions->isNotEmpty())
    <section class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
        @foreach($dashboardActions as $action)
            @php
                $targetUrl = $action['url'] ?? match($action['label']) {
                    'Valuasi Persediaan' => route('finance.valuation'),
                    'Faktur Pembelian' => route('purchase-invoices.index'),
                    'Stok Seluruh Gudang' => route('reports.index', ['report' => 'stock']),
                    'Pantau Gudang' => route('inventory.stock.index'),
                    'Review Opname' => route('inventory.stock-opname.index'),
                    'Validasi Penerimaan' => route('inventory.stock-in.purchase-orders'),
                    'Barang Keluar' => route('inventory.stock-out.create'),
                    'Terima Transfer', 'Terima Barang' => route('inventory.transfers.index'),
                    'Stok Outlet' => route('inventory.stock.index'),
                    default => null,
                };
            @endphp
            @if($targetUrl)
                <a href="{{ $targetUrl }}" class="group flex items-center gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs hover:border-slate-300 hover:shadow-xs transition-all">
            @else
                <button type="button" onclick="openQuickActionModal(@js($action['label']))" class="group flex items-center gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs hover:border-slate-300 hover:shadow-xs transition-all text-left w-full">
            @endif
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isFinance ? 'bg-rose-50 text-[#DC143C]' : 'bg-slate-100 text-slate-800' }} group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[22px]">{{ $action['icon'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-xs font-bold text-slate-900 group-hover:text-[#DC143C] transition-colors truncate">
                        {{ $action['label'] }}
                    </span>
                    <span class="block text-[11px] text-slate-500 truncate mt-0.5">
                        @if($action['label'] === 'Valuasi Persediaan')
                            Snapshot WAC & Nilai Buku
                        @elseif($action['label'] === 'Faktur Pembelian')
                            Review Dokumen & Pelunasan
                        @elseif($action['label'] === 'Stok Seluruh Gudang')
                            Rekap Saldo Multi-Gudang
                        @else
                            Buka modul operasional &rarr;
                        @endif
                    </span>
                </div>
                <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all">chevron_right</span>
            @if($targetUrl)</a>@else</button>@endif
        @endforeach

        {{-- Movement History Shortcut --}}
        <a href="{{ route('inventory.movements.index') }}" class="group flex items-center gap-3.5 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-2xs hover:border-slate-300 hover:shadow-xs transition-all">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined text-[22px]">history</span>
            </div>
            <div class="min-w-0 flex-1">
                <span class="block text-xs font-bold text-slate-900 group-hover:text-[#DC143C] transition-colors truncate">Riwayat Mutasi</span>
                <span class="block text-[11px] text-slate-500 truncate mt-0.5">Timeline Pergerakan Stok</span>
            </div>
            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all">chevron_right</span>
        </a>
    </section>
    @endif

    {{-- 3. Top 4 KPI Cards (Desktop) --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @if($isFinance)
            {{-- Finance KPI 1: Valuasi Persediaan --}}
            <a href="{{ route('finance.valuation') }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Valuasi Persediaan</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-[#DC143C] group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">calculate</span>
                    </div>
                </div>
                <div>
                    <p class="text-xl font-bold font-poppins text-slate-900">Snapshot WAC</p>
                    <p class="text-xs text-slate-500 mt-0.5">Valuasi & Status Biaya</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-[#DC143C]">
                    <span>Buka Valuasi</span>
                    <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </div>
            </a>

            {{-- Finance KPI 2: Faktur Pembelian --}}
            <a href="{{ route('purchase-invoices.index') }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Faktur Pembelian</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                    </div>
                </div>
                <div>
                    <p class="text-xl font-bold font-poppins text-slate-900">Tagihan Supplier</p>
                    <p class="text-xs text-slate-500 mt-0.5">Review Invoice & Pelunasan</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-amber-700">
                    <span>Lihat Faktur</span>
                    <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                </div>
            </a>
        @else
            {{-- Non-Finance KPI 1: Pengguna / Produk Aktif --}}
            <a href="{{ $canViewLowStock ? route('low-stock.index') : '#' }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $isSuperAdmin ? 'Pengguna Aktif' : 'Produk Aktif' }}</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">{{ $isSuperAdmin ? 'group' : 'inventory_2' }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-xl font-bold font-poppins text-slate-900">{{ $isSuperAdmin ? $activeUserCount : $activeProductCount }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $isSuperAdmin ? $roleCount.' role sistem' : $activeLocationCount.' lokasi' }}</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-400">
                    {{ $activeProductCount }} produk aktif &middot; {{ $activeLocationCount }} lokasi
                </div>
            </a>

            {{-- Non-Finance KPI 2: Kedaluwarsa --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Kedaluwarsa</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <span class="material-symbols-outlined text-[20px]">schedule</span>
                    </div>
                </div>
                <div>
                    <p class="text-xl font-bold font-poppins text-slate-900">&mdash;</p>
                    <p class="text-xs text-slate-500 mt-0.5">Batch belum tersedia</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-400">
                    Pantau umur simpan barang
                </div>
            </div>
        @endif

        {{-- Card 3: Katalog Produk --}}
        <a href="{{ route('reports.index', ['report' => 'stock']) }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Katalog Produk</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">category</span>
                </div>
            </div>
            <div>
                <p class="text-xl font-bold font-poppins text-slate-900">{{ $activeProductCount }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $activeProductCount }} produk aktif di {{ $activeLocationCount }} lokasi</p>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold text-emerald-700">
                <span>Laporan Stok</span>
                <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
            </div>
        </a>

        {{-- Card 4: Stok Minimum / Alert --}}
        <a href="{{ $canViewLowStock ? route('low-stock.index') : '#' }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Stok Minimum</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $lowStockAlertCount ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500' }} group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                </div>
            </div>
            <div>
                <p data-low-stock-alert-count class="text-xl font-bold font-poppins {{ $lowStockAlertCount ? 'text-rose-700' : 'text-slate-900' }}">
                    {{ $canViewLowStock ? $lowStockAlertCount : '—' }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $canViewLowStock ? 'Produk perlu restock' : 'Tidak tersedia' }}</p>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] font-semibold {{ $lowStockAlertCount ? 'text-rose-700' : 'text-slate-400' }}">
                <span>{{ $lowStockAlertCount ? 'Lihat Peringatan' : 'Stok Terkendali' }}</span>
                <span class="material-symbols-outlined text-[14px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
            </div>
        </a>
    </section>

    {{-- Hidden element for live stock updates script compatibility --}}
    <div id="desktop-live-stock" class="hidden"></div>

    {{-- 4. Main 2-Column Section (Table + Sidebar) --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start min-w-0">

        {{-- Left: Data Stok Table (8/12) --}}
        <section class="xl:col-span-8 bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden min-w-0">
            <div class="p-5 sm:p-6 pb-4 border-b border-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                            </span>
                            <h2 class="font-poppins text-lg font-bold text-slate-900">Data Saldo Stok Fisik</h2>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Saldo aktual dihitung dari transaksi stok masuk dan keluar secara realtime.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Export Dropdown --}}
                        <div class="relative">
                            <button type="button" id="stock-export-button" aria-expanded="false" onclick="toggleStockMenu('export')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-[16px] text-slate-500">download</span>
                                Export
                                <span class="material-symbols-outlined text-[14px] text-slate-400">expand_more</span>
                            </button>
                            <div id="stock-export-menu" class="hidden absolute right-0 z-30 mt-1.5 w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl">
                                <button type="button" onclick="exportStockFile('xlsx')" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] text-emerald-600">table_view</span>
                                    <span>Excel (.xlsx)</span>
                                </button>
                                <button type="button" onclick="exportStockFile('pdf')" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs font-medium text-slate-700 hover:bg-rose-50 hover:text-rose-800 transition-colors">
                                    <span class="material-symbols-outlined text-[16px] text-rose-600">picture_as_pdf</span>
                                    <span>PDF (.pdf)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Category Navigation Pills --}}
                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-slate-100 overflow-x-auto scrollbar-none">
                    <button type="button" onclick="setDesktopCategory('all', this)" class="d-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold text-[#DC143C] bg-rose-50 border border-rose-200/60 transition-all">Semua Kategori</button>
                    @foreach($stockCategories as $category)
                        <button type="button" onclick="setDesktopCategory(@js($category->name), this)" class="d-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 border border-transparent transition-all">{{ $category->name }}</button>
                    @endforeach
                </div>

                {{-- INLINE REALTIME FILTER TOOLBAR --}}
                <div class="mt-4 flex flex-wrap items-center gap-2.5 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/70">
                    {{-- Instant Search input --}}
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <span class="material-symbols-outlined text-[16px]">search</span>
                        </span>
                        <input type="text" id="desktop-table-search" oninput="filterDesktopTable(this.value)" placeholder="Cari nama produk, SKU..." class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-8 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:border-[#DC143C] focus:outline-none transition-all">
                    </div>

                    {{-- Category Combobox --}}
                    <div class="relative min-w-[150px]">
                        <select id="stock-filter-category" onchange="applyStockFilters()" class="w-full appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:border-[#DC143C] focus:outline-none transition-all">
                            <option value="all">Kategori: Semua</option>
                            @foreach($stockCategories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-[16px]">expand_more</span>
                        </span>
                    </div>

                    {{-- Status Combobox --}}
                    <div class="relative min-w-[130px]">
                        <select id="stock-filter-status" onchange="applyStockFilters()" class="w-full appearance-none rounded-lg border border-slate-200 bg-white py-1.5 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:border-[#DC143C] focus:outline-none transition-all">
                            <option value="all">Status: Semua</option>
                            <option value="available">Stok Tersedia</option>
                            <option value="empty">Stok Habis</option>
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                            <span class="material-symbols-outlined text-[16px]">expand_more</span>
                        </span>
                    </div>

                    {{-- Reset button --}}
                    <button type="button" onclick="resetStockFilters()" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">close</span>
                        Reset
                    </button>
                </div>
            </div>

            {{-- Table View --}}
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[620px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-y border-slate-200/80 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-3">SKU</th>
                            <th class="py-3 px-3">Produk</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3 text-right">Saldo Stok</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-4 text-right w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs" id="desktop-table-body">
                        @forelse($stockItems as $idx => $stock)
                            <tr class="d-row hover:bg-slate-50/70 transition-colors" data-category="{{ $stock->category->name }}" data-stock="{{ $stock->current_stock }}">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-[11px]">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-3">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-medium text-slate-700">
                                        {{ $stock->sku }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <a href="{{ route('inventory.stock.show', $stock) }}" class="flex items-center gap-2.5 group">
                                        <img src="{{ $stock->image_path ? asset('storage/'.$stock->image_path) : asset('images/product-placeholder.svg') }}" alt="" class="w-9 h-9 rounded-lg object-cover border border-slate-100 shrink-0">
                                        <div>
                                            <div class="font-semibold text-slate-900 group-hover:text-[#DC143C] transition-colors leading-tight">{{ $stock->name }}</div>
                                            <span class="text-[11px] text-slate-400">{{ $stock->unit->symbol }}</span>
                                        </div>
                                    </a>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                        {{ $stock->category->name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-right font-poppins font-bold text-slate-900">
                                    <span data-dashboard-stock="{{ $stock->id }}">{{ \App\Support\QuantityFormatter::format($stock->current_stock) }}</span>
                                    <span class="text-[11px] font-normal text-slate-400 ml-0.5">{{ $stock->unit->symbol }}</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $stock->current_stock > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $stock->current_stock > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ $stock->current_stock > 0 ? 'Tersedia' : 'Habis' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('inventory.stock.show', $stock) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">
                                        <span>Detail</span>
                                        <span class="material-symbols-outlined text-[14px] text-slate-400">chevron_right</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="table-initial-empty"><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada produk aktif. Periksa data produk dan transaksi stok masuk.</td></tr>
                        @endforelse
                        <tr id="table-empty-row" style="display: none;">
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-[28px] text-slate-300 mb-1">search_off</span>
                                    <p class="font-medium text-slate-700">Tidak ada produk yang cocok</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau reset filter combobox.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Table Footer Summary --}}
            <div class="px-6 py-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-slate-50/50">
                <div>
                    Menampilkan <strong id="table-visible-count" class="font-semibold text-slate-800">{{ $stockItems->count() }}</strong> dari {{ $stockItems->count() }} produk terdaftar
                </div>
                <div class="text-[11px] text-slate-400">
                    Pembaruan saldo otomatis berjalan setiap 10 detik
                </div>
            </div>
        </section>

        {{-- Right: Executive Sidebar (4/12) --}}
        <aside class="xl:col-span-4 space-y-5 min-w-0 w-full">
            @if($isFinance)
                {{-- Finance Center Card --}}
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-[#DC143C]">
                                <span class="material-symbols-outlined text-[18px]">account_balance</span>
                            </span>
                            <h2 class="text-sm font-bold font-poppins text-slate-900">Pusat Finansial & Valuasi</h2>
                        </div>
                        <span class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-semibold text-rose-700">Finance</span>
                    </div>
                    <div class="mt-3.5 space-y-2.5">
                        <a href="{{ route('finance.valuation') }}" class="group flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200 text-[#DC143C]">
                                <span class="material-symbols-outlined text-[18px]">calculate</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-900 group-hover:text-[#DC143C] transition-colors">Valuasi Persediaan WAC</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Snapshot nilai persediaan dan status harga buku</span>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all mt-1">chevron_right</span>
                        </a>

                        <a href="{{ route('purchase-invoices.index') }}" class="group flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200 text-amber-600">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-900 group-hover:text-amber-700 transition-colors">Faktur Pembelian Vendor</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Pemeriksaan tagihan supplier & status pelunasan</span>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all mt-1">chevron_right</span>
                        </a>

                        <a href="{{ route('reports.index', ['report' => 'purchasing']) }}" class="group flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200 text-blue-600">
                                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-900 group-hover:text-blue-700 transition-colors">Laporan Pengadaan PO</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Monitoring barang datang vs pesanan PO</span>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all mt-1">chevron_right</span>
                        </a>
                    </div>
                </div>

                {{-- Audit & Logistik Terbuku Card --}}
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">monitoring</span>
                            </span>
                            <h2 class="text-sm font-bold font-poppins text-slate-900">Audit & Logistik Terbuku</h2>
                        </div>
                    </div>
                    <div class="mt-3.5 space-y-2.5">
                        <a href="{{ route('inventory.movements.index') }}" class="group flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-slate-500">history</span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-slate-900">Riwayat Mutasi Barang</span>
                            </div>
                            <span class="material-symbols-outlined text-[15px] text-slate-400 group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                        </a>
                        <a href="{{ route('reports.index', ['report' => 'stock']) }}" class="group flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-emerald-600">inventory_2</span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-slate-900">Rekap Stok Seluruh Lokasi</span>
                            </div>
                            <span class="material-symbols-outlined text-[15px] text-slate-400 group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                        </a>
                        <a href="{{ route('low-stock.index') }}" class="group flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-[18px] text-amber-600">notifications_active</span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-slate-900">Peringatan Stok Minimum</span>
                            </div>
                            <span class="material-symbols-outlined text-[15px] text-slate-400 group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @else
                {{-- Operational Sidebar for other roles --}}
                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-sm font-semibold font-poppins text-slate-900">Pergerakan Stok</h2>
                        <span class="text-xs text-slate-500">7 hari terakhir</span>
                    </div>
                    @if($inventorySummaryAvailable)
                        <div class="flex items-center gap-4 my-3 text-xs text-slate-600 font-medium">
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#DC143C]"></span><span>Usage (kg)</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#708090]"></span><span>Waste (kg)</span></div>
                        </div>
                        <div class="w-full overflow-hidden">
                            <svg class="w-full h-36 pt-1" viewBox="0 0 380 160" preserveAspectRatio="xMidYMid meet">
                                <line x1="20" y1="20" x2="370" y2="20" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>
                                <line x1="20" y1="60" x2="370" y2="60" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>
                                <line x1="20" y1="100" x2="370" y2="100" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>
                                <line x1="20" y1="140" x2="370" y2="140" stroke="#EAEAEA" stroke-width="1"/>
                                <g transform="translate(30,0)"><rect x="0" y="30" width="12" height="110" rx="2" fill="#DC143C"/><rect x="14" y="105" width="12" height="35" rx="2" fill="#708090"/><text x="13" y="154" text-anchor="middle" font-size="9" fill="#888">1 Oct</text></g>
                                <g transform="translate(100,0)"><rect x="0" y="20" width="12" height="120" rx="2" fill="#DC143C"/><rect x="14" y="115" width="12" height="25" rx="2" fill="#708090"/><text x="13" y="154" text-anchor="middle" font-size="9" fill="#888">2 Oct</text></g>
                                <g transform="translate(170,0)"><rect x="0" y="35" width="12" height="105" rx="2" fill="#DC143C"/><rect x="14" y="110" width="12" height="30" rx="2" fill="#708090"/><text x="13" y="154" text-anchor="middle" font-size="9" fill="#888">3 Oct</text></g>
                                <g transform="translate(240,0)"><rect x="0" y="25" width="12" height="115" rx="2" fill="#DC143C"/><rect x="14" y="105" width="12" height="35" rx="2" fill="#708090"/><text x="13" y="154" text-anchor="middle" font-size="9" fill="#888">4 Oct</text></g>
                                <g transform="translate(310,0)"><rect x="0" y="45" width="12" height="95" rx="2" fill="#DC143C"/><rect x="14" y="115" width="12" height="25" rx="2" fill="#708090"/><text x="13" y="154" text-anchor="middle" font-size="9" fill="#888">5 Oct</text></g>
                            </svg>
                        </div>
                    @else
                        <div class="py-8 text-center text-xs text-slate-400">
                            <span class="material-symbols-outlined text-[28px] text-slate-300 mb-1">analytics</span>
                            <p class="font-medium text-slate-600">Belum ada transaksi stok untuk diringkas.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Transaksi masuk dan keluar akan tampil di sini.</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-2">
                        <h2 class="text-sm font-semibold font-poppins text-slate-900">Prioritas Kedaluwarsa</h2>
                        <span class="text-xs font-semibold text-[#DC143C]">Hari ini</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-3">Barang yang masa simpannya perlu diperhatikan.</p>
                    @if($inventorySummaryAvailable)
                        <div class="divide-y divide-slate-100">
                            <div class="py-2.5 flex items-center justify-between gap-3"><div class="flex items-center gap-2 min-w-0"><img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=80&auto=format&fit=crop&q=80" alt="Selada" class="w-8 h-8 rounded-lg object-cover"><div class="truncate"><div class="font-semibold text-xs text-slate-900">Selada Romaine</div><div class="text-[10px] text-[#DC143C] font-semibold">24 days left</div></div></div><div class="text-right shrink-0"><div class="text-xs font-semibold text-slate-900 font-plex-mono">13 kg</div><span class="text-[10px] font-semibold text-[#DC143C]">Use today</span></div></div>
                        </div>
                        <a href="{{ route('inventory.stock.index') }}" class="block text-center text-xs font-semibold text-slate-600 hover:text-[#DC143C] pt-3 mt-1 border-t border-slate-100">Lihat daftar lengkap &rsaquo;</a>
                    @else
                        <div class="py-6 text-center text-xs text-slate-400">
                            <span class="material-symbols-outlined text-[28px] text-slate-300 mb-1">schedule</span>
                            <p class="font-medium text-slate-600">Data batch dan tanggal kedaluwarsa belum tersedia.</p>
                            <a href="{{ route('inventory.stock.index') }}" class="mt-2 inline-block font-semibold text-[#DC143C] hover:underline">Buka daftar persediaan &rarr;</a>
                        </div>
                    @endif
                </div>
            @endif
        </aside>

    </div>
@endsection

{{-- ======================================================================= --}}
{{-- PAGE SCRIPTS                                                             --}}
{{-- ======================================================================= --}}
@section('page-scripts')
    // Live stock balance poller
    (function () {
        const mobile = document.getElementById('mobile-live-stock');
        const desktop = document.getElementById('desktop-live-stock');
        const endpoint = @js(route('dashboard.stock-live'));

        async function refreshStock() {
            try {
                const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const { items, low_stock_alert_count: lowStockAlertCount } = await response.json();
                if (lowStockAlertCount !== null && lowStockAlertCount !== undefined) {
                    document.querySelectorAll('[data-low-stock-alert-count]').forEach(node => {
                        node.textContent = `${lowStockAlertCount} alert`;
                    });
                }
                const formatQuantity = quantity => Number(quantity).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                items.forEach(item => {
                    const balance = document.querySelector(`[data-dashboard-stock="${item.id}"]`);
                    if (balance) balance.textContent = formatQuantity(item.quantity);
                    const row = document.querySelector(`#desktop-table-body [data-dashboard-stock="${item.id}"]`)?.closest('.d-row');
                    if (row) row.dataset.stock = item.quantity;
                });
                const render = (item, compact) => `<div class="flex items-center gap-3 rounded-xl ${compact ? 'bg-surface-container-lowest p-2.5' : 'border border-gray-100 p-3'}"><img src="${item.image || @js(asset('images/product-placeholder.svg'))}" class="h-11 w-11 rounded-lg object-cover" alt=""><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">${escapeHtml(item.name)}</p><p class="text-xs ${compact ? 'text-secondary' : 'text-gray-500'}">${escapeHtml(item.sku)} · ${escapeHtml(item.category)}</p></div><b class="whitespace-nowrap text-sm">${formatQuantity(item.quantity)} ${escapeHtml(item.unit)}</b></div>`;
                const empty = '<p class="py-4 text-center text-sm text-secondary">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p>';
                if (mobile) mobile.innerHTML = items.length ? items.map(item => render(item, true)).join('') : empty;
                if (desktop) desktop.innerHTML = items.length ? items.map(item => render(item, false)).join('') : '<p class="py-4 text-sm text-gray-500">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p>';
            } catch (_) { /* keep last balance visible */ }
        }
        function escapeHtml(value) { return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char])); }
        window.setInterval(refreshStock, 10000);
    })();

    // Active filtering states
    let desktopCategory = 'all';
    let desktopSearch = '';
    let desktopStockStatus = 'all';

    function applyStockFilters() {
        const categorySelect = document.getElementById('stock-filter-category');
        const statusSelect = document.getElementById('stock-filter-status');
        const searchInput = document.getElementById('desktop-table-search');

        if (categorySelect) desktopCategory = categorySelect.value;
        if (statusSelect) desktopStockStatus = statusSelect.value;
        if (searchInput) desktopSearch = searchInput.value.toLowerCase().trim();

        let visibleCount = 0;
        const rows = document.querySelectorAll('#desktop-table-body .d-row');
        rows.forEach(row => {
            const cat = (row.dataset.category || '').toLowerCase();
            const stock = parseFloat(row.dataset.stock || 0);
            const text = row.innerText.toLowerCase();

            const matchCat = desktopCategory === 'all' || cat === desktopCategory.toLowerCase();
            const matchStatus = desktopStockStatus === 'all' || (desktopStockStatus === 'available' && stock > 0) || (desktopStockStatus === 'empty' && stock <= 0);
            const matchSearch = !desktopSearch || text.includes(desktopSearch);

            if (matchCat && matchStatus && matchSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('table-empty-row');
        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
        }

        const countEl = document.getElementById('table-visible-count');
        if (countEl) {
            countEl.textContent = visibleCount;
        }
    }

    function setDesktopCategory(cat, btn) {
        desktopCategory = cat;
        const categorySelect = document.getElementById('stock-filter-category');
        if (categorySelect) categorySelect.value = cat;

        document.querySelectorAll('.d-tab').forEach(b => {
            b.className = 'd-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 border border-transparent transition-all';
        });
        if (btn) {
            btn.className = 'd-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold text-[#DC143C] bg-rose-50 border border-rose-200/60 transition-all';
        }
        applyStockFilters();
    }

    function filterDesktopTable(query) {
        desktopSearch = (query || '').toLowerCase().trim();
        const searchInput = document.getElementById('desktop-table-search');
        if (searchInput && searchInput.value !== query) {
            searchInput.value = query;
        }
        applyStockFilters();
    }

    function resetStockFilters() {
        const categorySelect = document.getElementById('stock-filter-category');
        const statusSelect = document.getElementById('stock-filter-status');
        const searchInput = document.getElementById('desktop-table-search');

        if (categorySelect) categorySelect.value = 'all';
        if (statusSelect) statusSelect.value = 'all';
        if (searchInput) searchInput.value = '';

        desktopCategory = 'all';
        desktopStockStatus = 'all';
        desktopSearch = '';

        document.querySelectorAll('.d-tab').forEach((tab, index) => {
            tab.className = index === 0
                ? 'd-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold text-[#DC143C] bg-rose-50 border border-rose-200/60 transition-all'
                : 'd-tab whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 border border-transparent transition-all';
        });

        applyStockFilters();
    }

    function toggleStockMenu(name) {
        const menu = document.getElementById(`stock-${name}-menu`);
        const button = document.getElementById(`stock-${name}-button`);
        if (!menu) return;
        const opening = menu.classList.contains('hidden');
        document.querySelectorAll('#stock-export-menu').forEach(node => node.classList.add('hidden'));
        document.querySelectorAll('#stock-export-button').forEach(node => node.setAttribute('aria-expanded', 'false'));
        if (opening) {
            menu.classList.remove('hidden');
            if (button) button.setAttribute('aria-expanded', 'true');
            if (name === 'export') window.prepareStockExport?.();
        }
    }

    function exportStockFile(format) {
        const rows = [...document.querySelectorAll('#desktop-table-body .d-row')].filter(row => row.style.display !== 'none');
        const values = [['SKU', 'Produk', 'Kategori', 'Stok', 'Status'], ...rows.map(row => {
            const sku = row.querySelector('td:nth-child(2)')?.innerText.trim() || '';
            const name = row.querySelector('td:nth-child(3) .font-semibold')?.innerText.trim() || '';
            const cat = row.querySelector('td:nth-child(4)')?.innerText.trim() || '';
            const stock = row.querySelector('td:nth-child(5)')?.innerText.trim() || '';
            const status = row.querySelector('td:nth-child(6)')?.innerText.trim() || '';
            return [sku, name, cat, stock, status];
        })];
        if (values.length <= 1) {
            alert('Tidak ada data stok yang terlihat untuk diekspor.');
            return;
        }
        try {
            window.generateStockExport(format, values).catch(error => {
                console.error('Gagal mengekspor data stok:', error);
                alert('Export gagal. Muat ulang halaman dan coba lagi.');
            });
        } catch (error) {
            console.error('Gagal mengekspor data stok:', error);
            alert('Export gagal. Muat ulang halaman dan coba lagi.');
            return;
        }
        const exportMenu = document.getElementById('stock-export-menu');
        if (exportMenu) exportMenu.classList.add('hidden');
        const exportBtn = document.getElementById('stock-export-button');
        if (exportBtn) exportBtn.setAttribute('aria-expanded', 'false');
    }

    function openQuickActionModal(title) {
        alert(title + ' belum tersedia. Pencatatan transaksi akan aktif setelah fitur stok dibuat.');
    }
@endsection
