@extends('layouts.dashboard')

@php $pageTitle = 'Technolife - Inventory Management Dashboard'; @endphp

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
                    Halo, {{ auth()->user()->name }} 👋
                </h1>
                <p class="text-[13px] text-secondary mt-0.5">
                    {{ $isSuperAdmin ? 'Administrasi Sistem' : 'Ringkasan gudang hari ini' }}
                    <span class="font-semibold text-secondary">{{ $activeLocationCount }} lokasi · {{ $activeProductCount }} produk aktif</span>
                </p>
            </div>
            <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[22px]">inventory</span>
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

    <!-- 2. Quick Actions -->
    @if($dashboardActions->isNotEmpty())
    <div class="grid grid-cols-2 gap-3 w-full">
        @foreach($dashboardActions as $index => $action)
            @if(isset($action['url']))
                <a href="{{ $action['url'] }}" class="h-12 {{ $index === 0 ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-primary border-2 border-primary' }} text-[13px] font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] transition-transform">
            @else
                <button type="button" onclick="openQuickActionModal(@js($action['label']))" class="h-12 {{ $index === 0 ? 'bg-primary text-on-primary' : 'bg-surface-container-lowest text-primary border-2 border-primary' }} text-[13px] font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] transition-transform cursor-pointer">
            @endif
                <span class="material-symbols-outlined text-[18px]">{{ $action['icon'] }}</span>
                <span>{{ $action['label'] }}</span>
            @if(isset($action['url']))</a>@else</button>@endif
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

    <!-- 4. Stock List -->
    @if($inventorySummaryAvailable)
    <div class="flex flex-col gap-2.5 w-full pt-1">
        <div class="flex items-center justify-between">
            <h2 class="font-poppins text-base font-semibold text-on-surface">Daftar Stok</h2>
            <button type="button" onclick="setMobileFilter('all', document.querySelector('.m-filter-all'))" class="text-xs font-semibold text-primary flex items-center gap-0.5 hover:underline">
                <span>Lihat Semua</span><span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </button>
        </div>
        <div class="flex items-center gap-2 overflow-x-auto py-1 -mx-4 px-4 scrollbar-none" id="mobile-filter-container">
            <button type="button" onclick="setMobileFilter('all', this)" class="m-filter-chip m-filter-all whitespace-nowrap px-4 py-1.5 rounded-full bg-primary text-on-primary text-xs font-semibold shadow-xs">Semua</button>
            <button type="button" onclick="setMobileFilter('Sayur & Buah', this)" class="m-filter-chip whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container text-secondary text-xs font-semibold hover:bg-surface-variant transition-colors">Sayur &amp; Buah</button>
            <button type="button" onclick="setMobileFilter('Daging', this)" class="m-filter-chip whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container text-secondary text-xs font-semibold hover:bg-surface-variant transition-colors">Daging</button>
            <button type="button" onclick="setMobileFilter('Bahan Kering', this)" class="m-filter-chip whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container text-secondary text-xs font-semibold hover:bg-surface-variant transition-colors">Bahan Kering</button>
        </div>
    </div>
    @endif

    <div class="flex flex-col gap-3 w-full" id="mobile-card-list">
        @if($inventorySummaryAvailable)
        <div class="m-card bg-surface-container-lowest p-3.5 rounded-2xl shadow-xs border border-outline flex flex-col gap-2.5 active:bg-surface-container-low transition-colors" data-category="Daging">
            <div class="flex items-start gap-3">
                <img class="w-14 h-14 rounded-xl object-cover bg-surface-container-high shrink-0" src="https://images.unsplash.com/photo-1544025162-d76694265947?w=120&auto=format&fit=crop&q=80" alt="Wagyu">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-on-surface truncate">Daging Sapi Wagyu</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-[11px] font-semibold shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-tertiary"></span>Stok Aman</span>
                    </div>
                    <p class="font-plex-mono text-secondary text-[11px] mt-0.5 truncate">SKU-BFF-042 | Batch #B92</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 mt-1"><div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden"><div class="h-full bg-tertiary-container rounded-full" style="width: 85%"></div></div><span class="text-xs font-semibold text-on-surface w-8 text-right">85%</span></div>
            <div class="flex items-center justify-between pt-1"><span class="text-xs font-semibold text-on-surface">Sisa: 120 kg</span><span class="material-symbols-outlined text-secondary text-[18px]">chevron_right</span></div>
        </div>
        <div class="m-card bg-surface-container-lowest p-3.5 rounded-2xl shadow-xs border border-outline flex flex-col gap-2.5 active:bg-surface-container-low transition-colors" data-category="Bahan Kering">
            <div class="flex items-start gap-3">
                <img class="w-14 h-14 rounded-xl object-cover bg-surface-container-high shrink-0" src="https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=120&auto=format&fit=crop&q=80" alt="Minyak">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-on-surface truncate">Minyak Goreng Sawit 18L</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error text-[11px] font-semibold shrink-0"><span class="material-symbols-outlined text-[12px]">warning</span>Mau Habis</span>
                    </div>
                    <p class="font-plex-mono text-secondary text-[11px] mt-0.5 truncate">SKU-DRY-108 | Batch #MGS-230</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 mt-1"><div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden"><div class="h-full bg-error rounded-full" style="width: 12%"></div></div><span class="text-xs font-semibold text-error w-8 text-right">12%</span></div>
            <div class="flex items-center justify-between pt-1"><span class="text-xs font-semibold text-on-surface">Sisa: 3 Can</span><span class="material-symbols-outlined text-secondary text-[18px]">chevron_right</span></div>
        </div>
        <div class="m-card bg-surface-container-lowest p-3.5 rounded-2xl shadow-xs border border-outline flex flex-col gap-2.5 active:bg-surface-container-low transition-colors" data-category="Bahan Kering">
            <div class="flex items-start gap-3">
                <img class="w-14 h-14 rounded-xl object-cover bg-surface-container-high shrink-0" src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=120&auto=format&fit=crop&q=80" alt="Susu UHT">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-on-surface truncate">Susu UHT Full Cream 1L</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-highest text-warning text-[11px] font-semibold shrink-0"><span class="material-symbols-outlined text-[12px]">schedule</span>Mau Kadaluarsa</span>
                    </div>
                    <p class="font-plex-mono text-secondary text-[11px] mt-0.5 truncate">SKU-FSH-019 | Batch #SFC-771</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 mt-1"><div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden"><div class="h-full bg-warning rounded-full" style="width: 40%"></div></div><span class="text-xs font-semibold text-warning w-8 text-right">40%</span></div>
            <div class="flex items-center justify-between pt-1"><span class="text-xs font-semibold text-on-surface">Sisa: 24 Pcs</span><span class="material-symbols-outlined text-secondary text-[18px]">chevron_right</span></div>
        </div>
        @else
            <div class="rounded-2xl border border-outline bg-white p-5 text-center text-sm text-secondary">Ringkasan batch dan kedaluwarsa belum tersedia. Saldo aktual tampil di bagian berikutnya.</div>
        @endif
    </div>

    <!-- Quick Action Cards (Mobile) -->
    <div class="flex flex-col gap-3 w-full">
        
        <!-- Stock Opname Card -->
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

        <!-- Stock Adjustment Card -->
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

        <!-- Movement History Card -->
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

    <!-- Live Stock Section (Mobile) -->
    <section class="rounded-2xl border border-outline bg-white p-4">
        <div class="flex items-center justify-between"><h2 class="font-poppins font-semibold">Saldo stok aktual</h2><span class="text-[11px] text-secondary">Live · refresh 10 detik</span></div>
        <div id="mobile-live-stock" class="mt-3 space-y-2">
            @forelse($stockItems as $stock)
                <div class="flex items-center gap-3 rounded-xl bg-surface-container-lowest p-2.5"><img src="{{ $stock->image_path ? asset('storage/'.$stock->image_path) : asset('images/product-placeholder.svg') }}" class="h-11 w-11 rounded-lg object-cover" alt=""><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $stock->name }}</p><p class="text-xs text-secondary">{{ $stock->sku }} · {{ $stock->category->name }}</p></div><b class="text-sm">{{ \App\Support\QuantityFormatter::format($stock->current_stock) }} {{ $stock->unit->symbol }}</b></div>
            @empty <p class="py-4 text-center text-sm text-secondary">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p> @endforelse
        </div>
        @if(auth()->user()->hasPermissionTo('stock.manage'))
        <div class="mt-3 flex flex-wrap gap-2">
            <a class="inline-flex rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white active:scale-95 transition-transform" href="{{ route('inventory.stock-in.create') }}">Catat Stock In</a>
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

    <!-- Quick Actions Grid -->
    <section class="mb-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Stock Opname Card -->
        @if(auth()->user()->hasPermissionTo('opname.manage') || auth()->user()->hasPermissionTo('opname.approve'))
        <a href="{{ route('inventory.stock-opname.index') }}" class="group bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-gray-300 transition-all">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[22px]">inventory_2</span>
                </div>
                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Verifikasi</span>
            </div>
            <h3 class="font-poppins text-base font-semibold text-gray-900 mb-1.5">Stock Opname</h3>
            <p class="text-xs text-gray-600 leading-relaxed mb-4">Cocokkan stok fisik dengan sistem. Cegah selisih sebelum terlambat.</p>
            <div class="flex items-center gap-1.5 text-gray-900 text-xs font-semibold">
                <span>Mulai Verifikasi</span>
                <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </a>
        @endif

        <!-- Stock Adjustment Card -->
        @if(auth()->user()->hasPermissionTo('adjust.manage'))
        <a href="{{ route('inventory.stock-adjustments.index') }}" class="group bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-gray-300 transition-all">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[22px]">tune</span>
                </div>
                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Koreksi</span>
            </div>
            <h3 class="font-poppins text-base font-semibold text-gray-900 mb-1.5">Stock Adjustment</h3>
            <p class="text-xs text-gray-600 leading-relaxed mb-4">Perbaiki saldo karena kerusakan, expired, atau selisih audit.</p>
            <div class="flex items-center gap-1.5 text-gray-900 text-xs font-semibold">
                <span>Buat Adjustment</span>
                <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </a>
        @endif

        <!-- Movement History Card -->
        <a href="{{ route('inventory.movements.index') }}" class="group bg-white border border-gray-200 rounded-xl p-5 shadow-sm hover:shadow-md hover:border-gray-300 transition-all">
            <div class="flex items-start justify-between mb-3">
                <div class="w-11 h-11 rounded-lg bg-gray-900 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[22px]">history</span>
                </div>
                <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Timeline</span>
            </div>
            <h3 class="font-poppins text-base font-semibold text-gray-900 mb-1.5">Riwayat Mutasi</h3>
            <p class="text-xs text-gray-600 leading-relaxed mb-4">Lacak semua pergerakan barang. Audit lengkap dari pertama kali masuk.</p>
            <div class="flex items-center gap-1.5 text-gray-900 text-xs font-semibold">
                <span>Lihat Timeline</span>
                <span class="material-symbols-outlined text-[16px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </div>
        </a>

    </section>

    <!-- Live Stock Section -->
    <section class="mb-5 rounded-xl border border-[#EAEAEA] bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-poppins font-semibold text-gray-900">Saldo stok aktual</h2>
                <p class="text-xs text-gray-500">Saldo dihitung dari transaksi masuk dan keluar · update otomatis setiap 10 detik</p>
            </div>
            @if(auth()->user()->hasPermissionTo('stock.manage'))
            <div class="flex gap-2">
                <a href="{{ route('inventory.stock-in.create') }}" class="rounded-lg bg-[#DC143C] px-4 py-2 text-sm font-semibold text-white hover:bg-[#B8102F] transition-colors">Stock In</a>
                <a href="{{ route('inventory.stock-out.create') }}" class="rounded-lg border border-[#DC143C] px-4 py-2 text-sm font-semibold text-[#DC143C] hover:bg-[#FFF1F0] transition-colors">Stock Out</a>
            </div>
            @endif
        </div>
        <div id="desktop-live-stock" class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($stockItems as $stock)
                <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:border-gray-200 transition-colors"><img src="{{ $stock->image_path ? asset('storage/'.$stock->image_path) : asset('images/product-placeholder.svg') }}" class="h-11 w-11 rounded-lg object-cover" alt=""><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $stock->name }}</p><p class="text-xs text-gray-500">{{ $stock->sku }} · {{ $stock->category->name }}</p></div><b class="whitespace-nowrap text-sm">{{ \App\Support\QuantityFormatter::format($stock->current_stock) }} {{ $stock->unit->symbol }}</b></div>
            @empty <p class="py-4 text-sm text-gray-500">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p> @endforelse
        </div>
    </section>

    <!-- 1. Top 4 KPI Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">

        <!-- Card 1: Pengguna / Produk Aktif -->
        <a href="{{ $canViewLowStock ? route('low-stock.index') : '#' }}" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-gray-300 transition-all overflow-hidden">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $isSuperAdmin ? 'Pengguna Aktif' : 'Produk Aktif' }}</p>
            <p class="text-xl font-semibold font-poppins text-gray-900 mb-1">{{ $isSuperAdmin ? $activeUserCount : $activeProductCount }}</p>
            <p class="text-xs text-gray-500">{{ $isSuperAdmin ? $roleCount.' role sistem' : $activeLocationCount.' lokasi' }}</p>
        </a>

        <!-- Card 2: Stok Minimum -->
        <a href="{{ $canViewLowStock ? route('low-stock.index') : '#' }}" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-gray-300 transition-all overflow-hidden">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
            </div>
            <p class="text-[10px] font-medium text-red-600 uppercase tracking-wider mb-1">Stok Minimum</p>
            <p data-low-stock-alert-count class="text-xl font-semibold font-poppins text-red-700 mb-1">{{ $canViewLowStock ? $lowStockAlertCount : '—' }}</p>
            <p class="text-xs text-gray-500">{{ $canViewLowStock ? 'Produk perlu restock' : 'Tidak tersedia' }}</p>
        </a>

        <!-- Card 3: Mendekati Kedaluwarsa -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-all overflow-hidden">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <p class="text-[10px] font-medium text-amber-600 uppercase tracking-wider mb-1">Kedaluwarsa</p>
            <p class="text-xl font-semibold font-poppins text-gray-900 mb-1">—</p>
            <p class="text-xs text-gray-500">Batch belum tersedia</p>
        </div>

        <!-- Card 4: Notifikasi WhatsApp -->
        <a @if(auth()->user()->hasPermissionTo('whatsapp.manage')) href="{{ route('admin.whatsapp-alerts.index') }}" @endif class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-gray-300 transition-all overflow-hidden">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider mb-1">WhatsApp Alert</p>
            <p class="text-xl font-semibold font-poppins text-gray-900 mb-1">{{ $whatsAppAlertsEnabled ? 'Aktif' : 'Nonaktif' }}</p>
            <p class="text-xs text-gray-500">{{ auth()->user()->hasPermissionTo('whatsapp.manage') ? 'Kelola notifikasi' : 'Dikelola admin' }}</p>
        </a>

    </section>

    <!-- 2. Table + Analytics -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start min-w-0">

        <!-- Left: Inventory Table (8/12) -->
        <section class="xl:col-span-8 bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden min-w-0">
            <div class="p-6 pb-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold font-poppins text-gray-900 tracking-tight">Data Stok</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Pantau barang dan pergerakan stok di seluruh lokasi.</p>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter<svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export<svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-6 mt-6 border-b border-[#EAEAEA] text-xs font-medium">
                    <button type="button" onclick="setDesktopCategory('all', this)" class="d-tab pb-3 font-semibold text-[#DC143C] border-b-2 border-[#DC143C] -mb-[1px]">All Items</button>
                    <button type="button" onclick="setDesktopCategory('Fresh Produce', this)" class="d-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors">Fresh Produce</button>
                    <button type="button" onclick="setDesktopCategory('Frozen Meat', this)" class="d-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors">Frozen Meat</button>
                    <button type="button" onclick="setDesktopCategory('Dry Pantry', this)" class="d-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors">Dry Pantry</button>
                    <button type="button" onclick="setDesktopCategory('Packaging', this)" class="d-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors">Packaging</button>
                </div>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[580px]">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-10 text-center"><input type="checkbox" id="d-select-all" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C]"></th>
                            <th class="py-3 px-3">SKU Code</th>
                            <th class="py-3 px-3">Item &amp; Batch</th>
                            <th class="py-3 px-3">Category</th>
                            <th class="py-3 px-3">Stock Bar</th>
                            <th class="py-3 px-3">Expiry (FEFO)</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3 text-center w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA] text-xs" id="desktop-table-body">
                        @if($inventorySummaryAvailable)
                        <tr class="d-row hover:bg-gray-50/70 transition-colors" data-category="Frozen Meat">
                            <td class="py-3.5 px-4 text-center"><input type="checkbox" class="d-row-check w-4 h-4 rounded border-gray-300 text-[#DC143C]"></td>
                            <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-BFF-042</td>
                            <td class="py-3.5 px-3"><div class="flex items-center gap-2.5"><img src="https://images.unsplash.com/photo-1544025162-d76694265947?w=100&auto=format&fit=crop&q=80" alt="Wagyu" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0"><div><div class="font-semibold text-gray-900 leading-tight">Daging Sapi Wagyu MB5</div><span class="text-[11px] text-gray-400">Batch #B92</span></div></div></td>
                            <td class="py-3.5 px-3 text-gray-600 font-medium">Frozen Meat</td>
                            <td class="py-3.5 px-3 min-w-[105px]"><div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1"><div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2"><div class="bg-[#28A745] h-full rounded-full" style="width: 85%;"></div></div><span>85%</span></div><div class="text-[10px] text-gray-400 font-plex-mono">120 kg / 140 kg</div></td>
                            <td class="py-3.5 px-3"><div class="font-semibold text-[#DC143C]">12 Nov 2026</div><div class="text-[10px] text-gray-400">128 days left</div></td>
                            <td class="py-3.5 px-3"><span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]"><span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>Optimal</span></td>
                            <td class="py-3.5 px-3 text-center text-gray-400 hover:text-gray-700 cursor-pointer">•••</td>
                        </tr>
                        <tr class="d-row hover:bg-gray-50/70 transition-colors" data-category="Dry Pantry">
                            <td class="py-3.5 px-4 text-center"><input type="checkbox" class="d-row-check w-4 h-4 rounded border-gray-300 text-[#DC143C]"></td>
                            <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-DRY-108</td>
                            <td class="py-3.5 px-3"><div class="flex items-center gap-2.5"><img src="https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=100&auto=format&fit=crop&q=80" alt="Minyak" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0"><div><div class="font-semibold text-gray-900 leading-tight">Minyak Goreng Sawit 18L</div><span class="text-[11px] text-gray-400">Batch #MGS-230</span></div></div></td>
                            <td class="py-3.5 px-3 text-gray-600 font-medium">Dry Pantry</td>
                            <td class="py-3.5 px-3 min-w-[105px]"><div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1"><div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2"><div class="bg-[#DC143C] h-full rounded-full" style="width: 12%;"></div></div><span>12%</span></div><div class="text-[10px] text-gray-400 font-plex-mono">3 Can / 24 Can</div></td>
                            <td class="py-3.5 px-3"><div class="font-semibold text-[#DC143C]">04 Mar 2027</div><div class="text-[10px] text-gray-400">210 days left</div></td>
                            <td class="py-3.5 px-3"><span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFF1F0] text-[#DC143C]"><span class="w-1.5 h-1.5 rounded-full bg-[#DC143C]"></span>Low Stock</span></td>
                            <td class="py-3.5 px-3 text-center text-gray-400 hover:text-gray-700 cursor-pointer">•••</td>
                        </tr>
                        <tr class="d-row hover:bg-gray-50/70 transition-colors" data-category="Fresh Produce">
                            <td class="py-3.5 px-4 text-center"><input type="checkbox" class="d-row-check w-4 h-4 rounded border-gray-300 text-[#DC143C]"></td>
                            <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-FSH-019</td>
                            <td class="py-3.5 px-3"><div class="flex items-center gap-2.5"><img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=100&auto=format&fit=crop&q=80" alt="Susu UHT" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0"><div><div class="font-semibold text-gray-900 leading-tight">Susu UHT Full Cream 1L</div><span class="text-[11px] text-gray-400">Batch #SFC-771</span></div></div></td>
                            <td class="py-3.5 px-3 text-gray-600 font-medium">Dairy / Fresh</td>
                            <td class="py-3.5 px-3 min-w-[105px]"><div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1"><div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2"><div class="bg-[#F59E0B] h-full rounded-full" style="width: 40%;"></div></div><span>40%</span></div><div class="text-[10px] text-gray-400 font-plex-mono">24 Pcs / 60 Pcs</div></td>
                            <td class="py-3.5 px-3"><div class="font-semibold text-[#DC143C]">09 Oct 2026</div><div class="text-[10px] text-gray-400">25 days left</div></td>
                            <td class="py-3.5 px-3"><span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#D97706]"><span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>Expiring Soon</span></td>
                            <td class="py-3.5 px-3 text-center text-gray-400 hover:text-gray-700 cursor-pointer">•••</td>
                        </tr>
                        @else
                        <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">Ringkasan batch dan kedaluwarsa belum tersedia. Saldo aktual tampil di bagian atas.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-[#EAEAEA] flex items-center justify-between text-xs text-gray-500">
                <div>Belum ada data stok untuk ditampilkan</div>
                <div class="flex items-center gap-1">
                    <button type="button" class="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-400">&lt;</button>
                    <button type="button" class="w-7 h-7 rounded bg-[#DC143C] text-white font-semibold flex items-center justify-center">1</button>
                    <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">2</button>
                    <button type="button" class="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-700">&gt;</button>
                </div>
            </div>
        </section>

        <!-- Right: Analytics (4/12) -->
        <aside class="xl:col-span-4 space-y-5 min-w-0 w-full">
            <!-- Stock Movement Chart -->
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-4 xl:p-5 shadow-xs overflow-hidden min-w-0">
                <div class="flex items-center justify-between pb-3 border-b border-[#EAEAEA]">
                    <h2 class="text-sm font-semibold font-poppins text-gray-900">Pergerakan Stok</h2>
                    <span class="text-xs text-gray-500">7 hari terakhir</span>
                </div>
                @if($inventorySummaryAvailable)
                <div class="flex items-center gap-4 my-3 text-xs text-gray-600 font-medium">
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
                    <p class="py-10 text-center text-sm text-gray-500">Belum ada transaksi stok untuk diringkas.</p>
                @endif
            </div>
            <!-- FEFO Queue -->
            <div class="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)]">
                <div class="flex items-center justify-between pb-2">
                    <h2 class="text-sm font-semibold font-poppins text-gray-900">Prioritas Kedaluwarsa</h2>
                    <span class="text-xs font-semibold text-[#DC143C]">Hari ini</span>
                </div>
                <p class="text-[11px] text-gray-400 mb-3">Barang yang masa simpannya perlu diperhatikan.</p>
                @if($inventorySummaryAvailable)
                <div class="divide-y divide-[#EAEAEA]">
                    <div class="py-2.5 flex items-center justify-between gap-3"><div class="flex items-center gap-2 min-w-0"><img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=80&auto=format&fit=crop&q=80" alt="Selada" class="w-8 h-8 rounded-lg object-cover"><div class="truncate"><div class="font-semibold text-xs text-gray-900">Selada Romaine</div><div class="text-[10px] text-[#DC143C] font-semibold">24 days left</div></div></div><div class="text-right shrink-0"><div class="text-xs font-semibold text-gray-900 font-plex-mono">13 kg</div><span class="text-[10px] font-semibold text-[#DC143C]">Use today</span></div></div>
                    <div class="py-2.5 flex items-center justify-between gap-3"><div class="flex items-center gap-2 min-w-0"><img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=80&auto=format&fit=crop&q=80" alt="Susu" class="w-8 h-8 rounded-lg object-cover"><div class="truncate"><div class="font-semibold text-xs text-gray-900">Susu UHT Full Cream</div><div class="text-[10px] text-[#DC143C] font-semibold">25 days left</div></div></div><div class="text-right shrink-0"><div class="text-xs font-semibold text-gray-900 font-plex-mono">24 Pcs</div><span class="text-[10px] font-semibold text-[#DC143C]">Use today</span></div></div>
                    <div class="py-2.5 flex items-center justify-between gap-3"><div class="flex items-center gap-2 min-w-0"><img src="https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=80&auto=format&fit=crop&q=80" alt="Tomat" class="w-8 h-8 rounded-lg object-cover"><div class="truncate"><div class="font-semibold text-xs text-gray-900">Tomat Merah Segar</div><div class="text-[10px] text-[#DC143C] font-semibold">27 days left</div></div></div><div class="text-right shrink-0"><div class="text-xs font-semibold text-gray-900 font-plex-mono">18 kg</div><span class="text-[10px] font-semibold text-[#DC143C]">Use today</span></div></div>
                </div>
                <a href="{{ route('inventory.stock.index') }}" class="block text-center text-xs font-semibold text-gray-600 hover:text-[#DC143C] pt-3 mt-1 border-t border-[#EAEAEA]">Lihat daftar lengkap &rsaquo;</a>
                @else
                    <p class="py-6 text-center text-sm text-gray-500">Data batch dan tanggal kedaluwarsa belum tersedia.</p>
                @endif
            </div>
        </aside>

    </div>
@endsection

{{-- ======================================================================= --}}
{{-- PAGE SCRIPTS                                                             --}}
{{-- ======================================================================= --}}
@section('page-scripts')
    // Update the displayed balance from the ledger without refreshing the page.
    (function () {
        const mobile = document.getElementById('mobile-live-stock');
        const desktop = document.getElementById('desktop-live-stock');
        if (!mobile && !desktop) return;
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
                const render = (item, compact) => `<div class="flex items-center gap-3 rounded-xl ${compact ? 'bg-surface-container-lowest p-2.5' : 'border border-gray-100 p-3'}"><img src="${item.image || @js(asset('images/product-placeholder.svg'))}" class="h-11 w-11 rounded-lg object-cover" alt=""><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">${escapeHtml(item.name)}</p><p class="text-xs ${compact ? 'text-secondary' : 'text-gray-500'}">${escapeHtml(item.sku)} · ${escapeHtml(item.category)}</p></div><b class="whitespace-nowrap text-sm">${formatQuantity(item.quantity)} ${escapeHtml(item.unit)}</b></div>`;
                const empty = '<p class="py-4 text-center text-sm text-secondary">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p>';
                if (mobile) mobile.innerHTML = items.length ? items.map(item => render(item, true)).join('') : empty;
                if (desktop) desktop.innerHTML = items.length ? items.map(item => render(item, false)).join('') : '<p class="py-4 text-sm text-gray-500">Belum ada saldo. Catat transaksi stok masuk untuk memulai.</p>';
            } catch (_) { /* keep the last successful balance visible while offline */ }
        }
        function escapeHtml(value) { return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char])); }
        window.setInterval(refreshStock, 10000);
    })();
    // Mobile filter chips
    function setMobileFilter(cat, btn) {
        document.querySelectorAll('.m-filter-chip').forEach(b => {
            b.className = 'm-filter-chip whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container text-secondary text-xs font-semibold hover:bg-surface-variant transition-colors';
        });
        btn.className = 'm-filter-chip whitespace-nowrap px-4 py-1.5 rounded-full bg-primary text-on-primary text-xs font-semibold shadow-xs';
        document.querySelectorAll('#mobile-card-list .m-card').forEach(card => {
            card.style.display = (cat === 'all' || card.getAttribute('data-category') === cat) ? '' : 'none';
        });
    }
    // Desktop category tabs
    function setDesktopCategory(cat, btn) {
        document.querySelectorAll('.d-tab').forEach(b => {
            b.classList.remove('font-semibold','text-[#DC143C]','border-[#DC143C]');
            b.classList.add('text-gray-500','border-transparent');
        });
        btn.classList.remove('text-gray-500','border-transparent');
        btn.classList.add('font-semibold','text-[#DC143C]','border-[#DC143C]');
        document.querySelectorAll('#desktop-table-body .d-row').forEach(row => {
            row.style.display = (cat === 'all' || row.getAttribute('data-category') === cat) ? '' : 'none';
        });
    }
    // Desktop search filter
    function filterDesktopTable(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('#desktop-table-body .d-row').forEach(row => {
            row.style.display = (q === '' || row.innerText.toLowerCase().includes(q)) ? '' : 'none';
        });
    }
    // Select all checkbox
    const dSelectAll = document.getElementById('d-select-all');
    if (dSelectAll) dSelectAll.addEventListener('change', () => {
        document.querySelectorAll('.d-row-check').forEach(cb => cb.checked = dSelectAll.checked);
    });
    function openQuickActionModal(title) {
        alert(title + ' belum tersedia. Pencatatan transaksi akan aktif setelah fitur stok dibuat.');
    }
@endsection
