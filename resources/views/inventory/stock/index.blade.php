@extends('layouts.dashboard')

@php
    $pageTitle = 'Data Stock & Saldo Fisik — Technolife';
    $canManageStock = auth()->user()->hasPermissionTo('stock.manage') || auth()->user()->hasRole('super_admin');
    $isWarehouseStaff = auth()->user()->isWarehouseStaff();
    $isSuperAdmin = auth()->user()->hasRole('super_admin');
    $collection = $products->getCollection();
    $totalProducts = $products->total();
    $lowStockCount = $collection->filter(fn($p) => !empty($lowStockByProduct[$p->id]) || $p->current_stock <= 0)->count();
    $healthyStockCount = $collection->filter(fn($p) => empty($lowStockByProduct[$p->id]) && $p->current_stock > 0)->count();
    $hasActiveFilters = (bool) array_filter($filters ?? []);
@endphp

{{-- ======================================================================= --}}
{{-- NAVBAR SEARCH HANDLER                                                    --}}
{{-- ======================================================================= --}}
@section('navbar-search-handler')oninput="filterStockTable(this.value)" placeholder="Cari nama, SKU, atau kategori produk..."@endsection

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- Mobile Header -->
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200/60 text-[10px] font-semibold text-[#DC143C] uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-[13px]">inventory_2</span>
                <span>Katalog & Saldo Fisik</span>
            </div>
            <h1 class="font-poppins text-xl font-bold text-slate-900 tracking-tight">Data Stock Gudang</h1>
            <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                <span>{{ $totalProducts }} produk terdaftar</span>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if($canManageStock)
                <a href="{{ $isWarehouseStaff ? route('inventory.stock-in.purchase-orders') : route('inventory.stock-in.create') }}"
                   title="{{ $isWarehouseStaff ? 'Validasi Penerimaan PO' : 'Catat Barang Masuk' }}"
                   class="h-9 px-3 bg-emerald-600 text-white text-xs font-semibold rounded-xl flex items-center gap-1 shadow-xs active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[16px]">{{ $isWarehouseStaff ? 'fact_check' : 'arrow_downward' }}</span>{{ $isWarehouseStaff ? 'Validasi PO' : 'Masuk' }}
                </a>
            @endif
            @if($isSuperAdmin)
                <a href="{{ route('admin.master-data.create', 'products') }}"
                   title="Tambah Produk Baru"
                   class="h-9 px-3 bg-[#DC143C] text-white text-xs font-semibold rounded-xl flex items-center gap-1 shadow-xs active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[16px]">add</span>Produk
                </a>
            @endif
        </div>
    </div>

    <!-- Mobile Operational KPI Grid (2x2) -->
    <div class="grid grid-cols-2 gap-2.5">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total SKU</span>
                <span class="material-symbols-outlined text-[18px] text-slate-600">inventory_2</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-slate-900">{{ number_format($totalProducts) }}</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Produk dalam katalog</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between {{ $lowStockCount > 0 ? 'text-rose-500' : 'text-slate-400' }}">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Perlu Perhatian</span>
                <span class="material-symbols-outlined text-[18px] {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-400' }}">warning</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                {{ $lowStockCount }}
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Stok menipis / habis</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-emerald-600">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Stok Aman</span>
                <span class="material-symbols-outlined text-[18px] text-emerald-600">check_circle</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-emerald-700">{{ $healthyStockCount }}</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Kondisi normal halaman</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <div class="flex items-center justify-between text-indigo-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Kategori</span>
                <span class="material-symbols-outlined text-[18px] text-indigo-600">category</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-bold text-slate-900">{{ $categories->count() }}</div>
            <p class="text-[10px] text-slate-500 mt-0.5">Klasifikasi aktif</p>
        </div>
    </div>

    <!-- Mobile Quick Filter Chips -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
        <button type="button" onclick="setMobileQuickFilter('all')" id="m-filter-all"
                class="m-quick-filter px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#DC143C] text-white shadow-2xs shrink-0 transition-all">
            Semua
        </button>
        <button type="button" onclick="setMobileQuickFilter('low')" id="m-filter-low"
                class="m-quick-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-50 shrink-0 transition-all flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            Stok Kritis ({{ $lowStockCount }})
        </button>
        <button type="button" onclick="setMobileQuickFilter('active')" id="m-filter-active"
                class="m-quick-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-50 shrink-0 transition-all">
            Hanya Aktif
        </button>
    </div>

    <!-- Mobile Search & Filter Form -->
    <form method="GET" action="{{ route('inventory.stock.index') }}" data-auto-filter class="flex flex-col gap-2">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </div>
            <input name="q" id="mobile-search-input" value="{{ $filters['q'] ?? '' }}"
                oninput="filterStockTable(this.value)"
                placeholder="Cari nama barang atau kode SKU..."
                class="w-full pl-10 pr-9 h-11 bg-white border border-slate-200/90 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all shadow-2xs">
            @if(!empty($filters['q']))
                <button type="button" onclick="clearMobileSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="relative">
                <select name="category" class="w-full h-10 bg-white border border-slate-200/90 rounded-xl pl-3 pr-7 text-xs text-slate-700 focus:border-[#DC143C] outline-none shadow-2xs appearance-none">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
            </div>

            <div class="relative">
                <select name="status" class="w-full h-10 bg-white border border-slate-200/90 rounded-xl pl-3 pr-7 text-xs text-slate-700 focus:border-[#DC143C] outline-none shadow-2xs appearance-none">
                    <option value="">Semua Status</option>
                    <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                </select>
                <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
            </div>
        </div>

        @if($hasActiveFilters)
            <div class="flex items-center justify-between text-xs pt-0.5">
                <span class="text-slate-500 font-medium">Filter sedang aktif</span>
                <a href="{{ route('inventory.stock.index') }}" class="inline-flex items-center gap-1 text-[#DC143C] font-semibold hover:underline">
                    <span class="material-symbols-outlined text-[14px]">refresh</span>Reset Filter
                </a>
            </div>
        @endif
    </form>

    <!-- Mobile Product Cards List -->
    @if($products->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-2xs">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-[#DC143C] flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-[26px]">inventory_2</span>
            </div>
            <p class="font-bold text-slate-800 text-sm">Produk Tidak Ditemukan</p>
            <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter kategori.</p>
            @if($hasActiveFilters)
                <a href="{{ route('inventory.stock.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-[#DC143C] hover:underline">
                    <span class="material-symbols-outlined text-[14px]">refresh</span>Kembalikan Filter
                </a>
            @endif
        </div>
    @else
        <div class="flex flex-col gap-3" id="mobile-product-list">
            @foreach($products as $product)
                @php
                    $isLowStock = !empty($lowStockByProduct[$product->id]) || $product->current_stock <= 0;
                    $locationsCount = count($stockByProductLocation[$product->id] ?? []);
                @endphp
                <div class="stock-product-card bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition-all overflow-hidden"
                     data-card-sku="{{ strtolower($product->sku) }}"
                     data-card-name="{{ strtolower($product->name) }}"
                     data-card-category="{{ strtolower($product->category->name) }}"
                     data-card-status="{{ $product->is_active ? 'active' : 'inactive' }}"
                     data-card-low-stock="{{ $isLowStock ? '1' : '0' }}">

                    <!-- Card Header (SKU & Status) -->
                    <div class="px-4 py-2.5 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-mono text-xs font-bold text-slate-800 bg-white px-2 py-0.5 rounded-md border border-slate-200 shadow-2xs truncate">
                                {{ $product->sku }}
                            </span>
                            <span class="text-[11px] font-medium text-slate-500 truncate">
                                {{ $product->category->name }}
                            </span>
                        </div>
                        <div class="shrink-0 flex items-center gap-1.5">
                            <span @class([
                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold',
                                'bg-emerald-50 text-emerald-700 border border-emerald-200/60' => $product->is_active,
                                'bg-slate-100 text-slate-600 border border-slate-200' => !$product->is_active,
                            ])>
                                <span class="w-1.5 h-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <img src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder.svg') }}"
                                 alt="{{ $product->name }}"
                                 class="w-12 h-12 rounded-xl object-cover shrink-0 border border-slate-100 shadow-2xs bg-slate-50">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('inventory.stock.show', $product) }}" class="text-sm font-bold text-slate-900 leading-snug hover:text-[#DC143C] transition-colors line-clamp-2">
                                    {{ $product->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500">
                                    <span>Satuan: <strong class="font-mono text-slate-700">{{ $product->unit->symbol }}</strong></span>
                                    <span>•</span>
                                    <span>{{ $locationsCount }} gudang terdata</span>
                                </div>
                            </div>
                        </div>

                        <!-- Highlight Saldo Stok Fisik -->
                        <div class="rounded-xl bg-slate-50 border border-slate-200/70 p-3 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 block">Total Saldo Fisik</span>
                                <div class="mt-0.5 text-base font-bold font-mono text-slate-900">
                                    <span data-stock-balance="{{ $product->id }}">
                                        {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit->symbol }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                @if($product->current_stock <= 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 text-[11px] font-bold">
                                        <span class="material-symbols-outlined text-[13px]">block</span>Stok Habis
                                    </span>
                                @elseif(!empty($lowStockByProduct[$product->id]))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-bold">
                                        <span class="material-symbols-outlined text-[13px]">warning</span>Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                                        <span class="material-symbols-outlined text-[13px]">check_circle</span>Stok Aman
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Breakdown Multi Lokasi Gudang -->
                        <div data-stock-location-breakdown="{{ $product->id }}" @class(['space-y-1.5' => !empty($stockByProductLocation[$product->id]), 'hidden' => empty($stockByProductLocation[$product->id])])>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 block">Distribusi per Lokasi:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($stockByProductLocation[$product->id] ?? [] as $stockLocation)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 border border-slate-200/80 px-2 py-1 text-[11px] font-medium text-slate-700">
                                        <span class="material-symbols-outlined text-[12px] text-slate-500">warehouse</span>
                                        <span class="text-slate-600">{{ $stockLocation['location_name'] }}:</span>
                                        <strong class="font-mono text-slate-900">{{ \App\Support\QuantityFormatter::format($stockLocation['quantity']) }}</strong>
                                        <span class="text-[10px] text-slate-500">{{ $product->unit->symbol }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Alert Peringatan Stok Rendah -->
                        <div data-low-stock-indicator="{{ $product->id }}" @class(['rounded-xl bg-rose-50/80 border border-rose-200/80 p-2.5 space-y-1' => !empty($lowStockByProduct[$product->id]), 'hidden' => empty($lowStockByProduct[$product->id])])>
                            <div class="flex items-center gap-1.5 text-rose-800 text-[11px] font-bold">
                                <span class="material-symbols-outlined text-[14px] text-rose-600">warning</span>
                                <span data-low-stock-status="{{ $product->id }}">Stok di bawah batas minimum:</span>
                            </div>
                            <div data-low-stock-locations="{{ $product->id }}" class="flex flex-wrap gap-1">
                                @foreach($lowStockByProduct[$product->id] ?? [] as $locationName)
                                    <span class="inline-flex items-center gap-0.5 rounded-md border border-rose-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">
                                        <span class="material-symbols-outlined text-[11px]">location_on</span>{{ $locationName }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="px-4 py-2.5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400">ID: #{{ $product->id }}</span>
                        <a href="{{ route('inventory.stock.show', $product) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-800 hover:text-[#DC143C] hover:border-[#DC143C]/40 active:scale-98 shadow-2xs transition-all">
                            <span>Buka Kartu Stok</span>
                            <span class="material-symbols-outlined text-[15px] text-[#DC143C]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Mobile Pagination -->
        @if($products->hasPages())
            <div class="pt-3 pb-2">{{ $products->links() }}</div>
        @endif
    @endif

</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT (>= lg)                                                 --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<div class="space-y-5">

    <!-- Page Header Row -->
    <div class="flex items-start justify-between gap-4 min-w-0">
        <div>
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                <span>Manajemen Inventaris</span>
                <span class="material-symbols-outlined text-[13px] text-slate-400">chevron_right</span>
                <span class="font-semibold text-slate-800">Data Stock Persediaan</span>
            </nav>
            <h1 class="font-poppins text-2xl font-bold text-slate-900 tracking-tight">Data Stock & Saldo Fisik</h1>
            <p class="text-xs text-slate-500 mt-1">Saldo fisik dihitung otomatis dari transaksi stok masuk, keluar, dan penyesuaian antar gudang.</p>
        </div>

        <!-- Action Header Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
            @if($canManageStock)
                @if($isWarehouseStaff)
                    <a href="{{ route('inventory.stock-in.purchase-orders') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs active:scale-98 transition-all">
                        <span class="material-symbols-outlined text-[17px]">fact_check</span>Validasi Penerimaan PO
                    </a>
                @else
                    <a href="{{ route('inventory.stock-in.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-xs active:scale-98 transition-all">
                        <span class="material-symbols-outlined text-[17px]">arrow_downward</span>Catat Barang Masuk
                    </a>
                @endif
                <a href="{{ route('inventory.stock-out.create') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-xs active:scale-98 transition-all">
                    <span class="material-symbols-outlined text-[17px]">arrow_upward</span>Catat Barang Keluar
                </a>
            @endif
            @if($isSuperAdmin)
                <a href="{{ route('admin.master-data.create', 'products') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#DC143C] hover:bg-[#b81032] text-white text-xs font-semibold rounded-xl shadow-xs active:scale-98 transition-all">
                    <span class="material-symbols-outlined text-[17px]">add</span>Tambah Produk
                </a>
            @endif
        </div>
    </div>

    <!-- 4 Field Operational KPI Cards (Desktop) -->
    <div class="grid grid-cols-4 gap-4">
        <!-- 1. Total SKU -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Katalog SKU Terdaftar</span>
                <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-poppins text-2xl font-bold text-slate-900">{{ number_format($totalProducts) }}</span>
                <span class="text-xs text-slate-500">produk</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Total produk aktif & nonaktif terdata</p>
        </div>

        <!-- 2. Stok Perlu Perhatian -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-500' }}">
                <span class="text-xs font-semibold uppercase tracking-wider {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-500' }}">Perlu Perhatian</span>
                <div class="w-8 h-8 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-700' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-poppins text-2xl font-bold {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $lowStockCount }}</span>
                <span class="text-xs text-slate-500">SKU di halaman</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Stok di bawah batas minimum / kosong</p>
        </div>

        <!-- 3. Stok Aman -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between text-emerald-600">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Kondisi Stok Aman</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">verified</span>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-poppins text-2xl font-bold text-emerald-700">{{ $healthyStockCount }}</span>
                <span class="text-xs text-slate-500">SKU aman</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Ketersediaan mencukupi ambang batas</p>
        </div>

        <!-- 4. Kategori & Gudang -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs hover:shadow-xs transition-shadow">
            <div class="flex items-center justify-between text-indigo-600">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Kategori Aktif</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">category</span>
                </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-poppins text-2xl font-bold text-slate-900">{{ $categories->count() }}</span>
                <span class="text-xs text-slate-500">kategori</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Klasifikasi komoditas gudang</p>
        </div>
    </div>

    <!-- Main Card -->
    <section class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden min-w-0">

        <!-- Toolbar Filter Bar -->
        <div class="p-4 sm:px-6 border-b border-slate-200/80 bg-slate-50/50">
            <form method="GET" action="{{ route('inventory.stock.index') }}" data-auto-filter class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[240px] max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </div>
                    <input name="q" id="desktop-search-input" value="{{ $filters['q'] ?? '' }}"
                        oninput="filterStockTable(this.value)"
                        placeholder="Ketik SKU atau nama barang..."
                        class="w-full pl-10 pr-8 h-10 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all shadow-2xs">
                    @if(!empty($filters['q']))
                        <button type="button" onclick="clearDesktopSearch()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    @endif
                </div>

                <!-- Quick Filter Pill: Kritis / Rendah -->
                <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200/60">
                    <button type="button" onclick="setDesktopQuickFilter('all')" id="d-filter-all"
                            class="d-quick-tab px-3 py-1 rounded-lg text-xs font-semibold bg-white text-[#DC143C] shadow-2xs transition-all">
                        Semua
                    </button>
                    <button type="button" onclick="setDesktopQuickFilter('low')" id="d-filter-low"
                            class="d-quick-tab px-3 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        Stok Kritis ({{ $lowStockCount }})
                    </button>
                </div>

                <!-- Category Dropdown -->
                <div class="relative">
                    <select name="category" class="h-10 bg-white border border-slate-200 rounded-xl pl-3 pr-8 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer shadow-2xs appearance-none">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>

                <!-- Status Dropdown -->
                <div class="relative">
                    <select name="status" class="h-10 bg-white border border-slate-200 rounded-xl pl-3 pr-8 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer shadow-2xs appearance-none">
                        <option value="">Semua Status</option>
                        <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Status: Aktif</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Status: Nonaktif</option>
                    </select>
                    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>

                <!-- Sort Dropdown -->
                <div class="relative">
                    <select name="sort" class="h-10 bg-white border border-slate-200 rounded-xl pl-3 pr-8 text-xs text-slate-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer shadow-2xs appearance-none">
                        <option value="name"       @selected(($filters['sort'] ?? 'name') === 'name')>Urut: Nama (A-Z)</option>
                        <option value="sku"        @selected(($filters['sort'] ?? '') === 'sku')>Urut: Kode SKU</option>
                        <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Urut: Produk Terbaru</option>
                    </select>
                    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>

                <!-- Reset Filter Button -->
                @if($hasActiveFilters)
                    <a href="{{ route('inventory.stock.index') }}" class="h-10 px-3.5 text-xs font-semibold text-[#DC143C] bg-rose-50 hover:bg-rose-100 rounded-xl flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[15px]">refresh</span>Reset
                    </a>
                @endif

                <!-- Total Count Badge -->
                <div class="ml-auto text-xs text-slate-500 font-medium hidden sm:block">
                    Total: <strong class="text-slate-800 font-semibold">{{ $totalProducts }}</strong> produk
                </div>
            </form>
        </div>

        <!-- Table -->
        @if($products->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-[#DC143C] flex items-center justify-center mx-auto mb-3">
                    <span class="material-symbols-outlined text-[32px]">inventory_2</span>
                </div>
                <p class="font-bold text-slate-800 text-sm">Produk Tidak Ditemukan</p>
                <p class="mt-1 text-xs text-slate-500">Coba sesuaikan kata kunci atau filter yang Anda pilih.</p>
                @if($hasActiveFilters)
                    <a href="{{ route('inventory.stock.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#DC143C] hover:underline">
                        <span class="material-symbols-outlined text-[16px]">refresh</span>Reset Semua Filter
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[768px]" id="stock-table">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-3 w-36">
                                <a href="{{ route('inventory.stock.index', array_merge($filters, ['sort' => 'sku', 'direction' => (($filters['sort'] ?? '') === 'sku' && ($filters['direction'] ?? 'asc') === 'asc') ? 'desc' : 'asc'])) }}"
                                   class="inline-flex items-center gap-1 hover:text-slate-900 transition-colors">
                                    <span>Kode SKU</span>
                                    <span class="material-symbols-outlined text-[14px]">{{ ($filters['sort'] ?? '') === 'sku' ? (($filters['direction'] ?? 'asc') === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                                </a>
                            </th>
                            <th class="py-3 px-3">
                                <a href="{{ route('inventory.stock.index', array_merge($filters, ['sort' => 'name', 'direction' => (($filters['sort'] ?? 'name') === 'name' && ($filters['direction'] ?? 'asc') === 'asc') ? 'desc' : 'asc'])) }}"
                                   class="inline-flex items-center gap-1 hover:text-slate-900 transition-colors">
                                    <span>Produk & Spesifikasi</span>
                                    <span class="material-symbols-outlined text-[14px]">{{ ($filters['sort'] ?? 'name') === 'name' ? (($filters['direction'] ?? 'asc') === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                                </a>
                            </th>
                            <th class="py-3 px-3 w-32">Kategori</th>
                            <th class="py-3 px-3 w-20 text-center">Satuan</th>
                            <th class="py-3 px-4 min-w-[220px]">Saldo Stok & Multi-Gudang</th>
                            <th class="py-3 px-3 w-40">Status & Kesehatan</th>
                            <th class="py-3 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs" id="stock-table-body">
                        @foreach($products as $product)
                        @php
                            $isLowStock = !empty($lowStockByProduct[$product->id]) || $product->current_stock <= 0;
                        @endphp
                        <tr class="s-row hover:bg-slate-50/70 transition-colors"
                            data-sku="{{ strtolower($product->sku) }}"
                            data-name="{{ strtolower($product->name) }}"
                            data-category="{{ strtolower($product->category->name) }}"
                            data-status="{{ $product->is_active ? 'active' : 'inactive' }}"
                            data-low-stock="{{ $isLowStock ? '1' : '0' }}">

                            <!-- Index -->
                            <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-[11px]">
                                {{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}
                            </td>

                            <!-- SKU Code -->
                            <td class="py-3.5 px-3">
                                <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200/80 shadow-2xs inline-block">
                                    {{ $product->sku }}
                                </span>
                            </td>

                            <!-- Product Info -->
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder.svg') }}"
                                         alt="{{ $product->name }}"
                                         class="w-10 h-10 rounded-xl object-cover shrink-0 border border-slate-100 bg-slate-50 shadow-2xs">
                                    <div class="min-w-0">
                                        <a href="{{ route('inventory.stock.show', $product) }}" class="font-semibold text-slate-900 hover:text-[#DC143C] transition-colors leading-tight line-clamp-1">
                                            {{ $product->name }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            ID: #{{ $product->id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3.5 px-3 text-slate-700">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200/60">
                                    {{ $product->category->name }}
                                </span>
                            </td>

                            <!-- Unit -->
                            <td class="py-3.5 px-3 text-center">
                                <span class="font-mono text-xs font-semibold text-slate-600 bg-slate-50 px-2 py-0.5 rounded border border-slate-200/60">
                                    {{ $product->unit->symbol }}
                                </span>
                            </td>

                            <!-- Saldo Stok & Multi-Lokasi -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-mono text-sm font-bold text-slate-900" data-stock-balance="{{ $product->id }}">
                                        {{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit->symbol }}
                                    </span>
                                </div>

                                <!-- Multi Location Chips -->
                                <div data-stock-location-breakdown="{{ $product->id }}" @class(['mt-1.5 flex flex-wrap gap-1' => !empty($stockByProductLocation[$product->id]), 'hidden' => empty($stockByProductLocation[$product->id])])>
                                    @foreach($stockByProductLocation[$product->id] ?? [] as $stockLocation)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 border border-slate-200/80 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                            <span class="material-symbols-outlined text-[12px] text-slate-500">warehouse</span>
                                            <span class="text-slate-600">{{ $stockLocation['location_name'] }}:</span>
                                            <strong class="font-mono text-slate-900">{{ \App\Support\QuantityFormatter::format($stockLocation['quantity']) }}</strong>
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- Status & Health -->
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-1.5">
                                    <span @class([
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $product->is_active,
                                        'bg-slate-100 text-slate-600 border border-slate-200' => !$product->is_active,
                                    ])>
                                        <span class="w-1.5 h-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>

                                <!-- Low Stock Warning Indicator -->
                                <div data-low-stock-indicator="{{ $product->id }}" @class(['mt-1.5 flex flex-wrap items-center gap-1' => !empty($lowStockByProduct[$product->id]), 'hidden' => empty($lowStockByProduct[$product->id])])>
                                    <span data-low-stock-status="{{ $product->id }}" class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                        <span class="material-symbols-outlined text-[11px]">warning</span>Stok rendah
                                    </span>
                                    <div data-low-stock-locations="{{ $product->id }}" class="flex flex-wrap gap-1">
                                        @foreach($lowStockByProduct[$product->id] ?? [] as $locationName)
                                            <span class="inline-flex items-center gap-0.5 rounded-md border border-rose-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-700">
                                                <span class="material-symbols-outlined text-[10px] text-rose-500">location_on</span>{{ $locationName }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('inventory.stock.show', $product) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:border-[#DC143C]/40 hover:text-[#DC143C] hover:bg-rose-50/50 shadow-2xs active:scale-95 transition-all">
                                    <span class="material-symbols-outlined text-[15px] text-[#DC143C]">open_in_new</span>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 bg-slate-50/40">
                <div>
                    Menampilkan <strong class="text-slate-800 font-semibold">{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong>
                    dari <strong class="text-slate-800 font-semibold">{{ $products->total() }}</strong> produk
                </div>
                <div>{{ $products->links() }}</div>
            </div>
        @endif

    </section>

</div>
@endsection

{{-- ======================================================================= --}}
{{-- PAGE SCRIPTS                                                             --}}
{{-- ======================================================================= --}}
@section('page-scripts')
    // Realtime background polling for stock balances & alerts (every 10s)
    (function () {
        const endpoint = @js(route('inventory.stock.live')) + window.location.search;
        async function refreshBalances() {
            try {
                const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                data.items.forEach(item => {
                    // Update Stock Balance Text
                    document.querySelectorAll(`[data-stock-balance="${item.id}"]`).forEach(node => {
                        const formatted = Number(item.quantity).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                        node.textContent = `${formatted} ${item.unit}`;
                    });

                    // Update Multi-Location Breakdown
                    document.querySelectorAll(`[data-stock-location-breakdown="${item.id}"]`).forEach(container => {
                        const rows = item.stock_by_location || [];
                        container.classList.toggle('hidden', rows.length === 0);
                        const labels = rows.map(row => {
                            const label = document.createElement('span');
                            label.className = 'inline-flex items-center gap-1 rounded-md bg-slate-100 border border-slate-200/80 px-2 py-0.5 text-[11px] font-medium text-slate-700';
                            const quantity = Number(row.quantity).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                            label.innerHTML = `<span class="material-symbols-outlined text-[12px] text-slate-500">warehouse</span><span class="text-slate-600">${row.location_name}:</span> <strong class="font-mono text-slate-900">${quantity}</strong>`;
                            return label;
                        });
                        container.replaceChildren(...labels);
                    });

                    // Update Low Stock Indicators
                    const locations = item.low_stock_locations || [];
                    document.querySelectorAll(`[data-low-stock-indicator="${item.id}"]`).forEach(node => {
                        node.classList.toggle('hidden', locations.length === 0);
                    });
                    document.querySelectorAll(`[data-low-stock-locations="${item.id}"]`).forEach(container => {
                        const locationLabels = locations.map(name => {
                            const label = document.createElement('span');
                            label.className = 'inline-flex items-center gap-0.5 rounded-md border border-rose-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-700';
                            const icon = document.createElement('span');
                            icon.className = 'material-symbols-outlined text-[10px] text-rose-500';
                            icon.textContent = 'location_on';
                            label.append(icon, document.createTextNode(name));
                            return label;
                        });
                        container.replaceChildren(...locationLabels);
                    });
                });
            } catch (_) { /* Keep last successful balances visible while offline */ }
        }
        refreshBalances();
        window.setInterval(refreshBalances, 10000);
    })();

    // State for Client-Side Filtering
    let currentSearchTerm = '';
    let currentFilterMode = 'all'; // 'all', 'low', 'active'

    function filterStockTable(query) {
        currentSearchTerm = (query || '').toLowerCase().trim();
        applyClientFilters();
    }

    function clearDesktopSearch() {
        const input = document.getElementById('desktop-search-input');
        if (input) input.value = '';
        currentSearchTerm = '';
        applyClientFilters();
    }

    function clearMobileSearch() {
        const input = document.getElementById('mobile-search-input');
        if (input) input.value = '';
        currentSearchTerm = '';
        applyClientFilters();
    }

    function setDesktopQuickFilter(mode) {
        currentFilterMode = mode;
        const allBtn = document.getElementById('d-filter-all');
        const lowBtn = document.getElementById('d-filter-low');

        if (allBtn && lowBtn) {
            if (mode === 'all') {
                allBtn.className = 'd-quick-tab px-3 py-1 rounded-lg text-xs font-semibold bg-white text-[#DC143C] shadow-2xs transition-all';
                lowBtn.className = 'd-quick-tab px-3 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1';
            } else if (mode === 'low') {
                allBtn.className = 'd-quick-tab px-3 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 transition-all';
                lowBtn.className = 'd-quick-tab px-3 py-1 rounded-lg text-xs font-semibold bg-white text-rose-600 shadow-2xs transition-all flex items-center gap-1';
            }
        }
        applyClientFilters();
    }

    function setMobileQuickFilter(mode) {
        currentFilterMode = mode;
        const filters = ['all', 'low', 'active'];
        filters.forEach(f => {
            const btn = document.getElementById(`m-filter-${f}`);
            if (!btn) return;
            if (f === mode) {
                btn.className = 'm-quick-filter px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#DC143C] text-white shadow-2xs shrink-0 transition-all' + (f === 'low' ? ' flex items-center gap-1' : '');
            } else {
                btn.className = 'm-quick-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-200/80 hover:bg-slate-50 shrink-0 transition-all' + (f === 'low' ? ' flex items-center gap-1' : '');
            }
        });
        applyClientFilters();
    }

    function applyClientFilters() {
        // Filter Desktop Table Rows
        document.querySelectorAll('#stock-table-body .s-row').forEach(row => {
            const textMatch = currentSearchTerm === '' || row.innerText.toLowerCase().includes(currentSearchTerm);
            let modeMatch = true;

            if (currentFilterMode === 'low') {
                modeMatch = row.getAttribute('data-low-stock') === '1';
            } else if (currentFilterMode === 'active') {
                modeMatch = row.getAttribute('data-status') === 'active';
            }

            row.style.display = (textMatch && modeMatch) ? '' : 'none';
        });

        // Filter Mobile Cards
        document.querySelectorAll('#mobile-product-list .stock-product-card').forEach(card => {
            const textMatch = currentSearchTerm === '' || card.innerText.toLowerCase().includes(currentSearchTerm);
            let modeMatch = true;

            if (currentFilterMode === 'low') {
                modeMatch = card.getAttribute('data-card-low-stock') === '1';
            } else if (currentFilterMode === 'active') {
                modeMatch = card.getAttribute('data-card-status') === 'active';
            }

            card.style.display = (textMatch && modeMatch) ? '' : 'none';
        });
    }
@endsection
