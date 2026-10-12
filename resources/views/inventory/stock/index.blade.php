@extends('layouts.dashboard')

@php $pageTitle = 'Data Stock — Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- NAVBAR SEARCH HANDLER                                                    --}}
{{-- ======================================================================= --}}
@section('navbar-search-handler')oninput="filterStockTable(this.value)" placeholder="Cari nama, SKU, kategori..."@endsection

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-lg font-semibold text-on-surface">Data Stock</h1>
            <p class="text-xs text-secondary mt-0.5">{{ $products->total() }} produk terdaftar · <span data-stock-live-updated>Memuat pembaruan stok…</span></p>
        </div>
        @if(auth()->user()->hasRole('super_admin'))
            <a href="{{ route('admin.master-data.create', 'products') }}"
               class="h-9 px-3.5 bg-primary text-on-primary text-xs font-semibold rounded-xl flex items-center gap-1.5 shadow-sm active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[16px]">add</span>Tambah
            </a>
        @endif
    </div>

    <!-- Search + Filter -->
    <form method="GET" class="flex flex-col gap-2">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </div>
            <input name="q" value="{{ $filters['q'] ?? '' }}"
                placeholder="Cari nama atau SKU..."
                class="w-full pl-9 pr-4 h-10 bg-surface-container-lowest border border-outline rounded-xl text-sm text-on-surface placeholder:text-secondary/60 focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none transition-all">
        </div>
        <div class="flex gap-2">
            <select name="category" class="flex-1 h-9 bg-surface-container-lowest border border-outline rounded-xl px-3 text-xs text-on-surface focus:border-primary outline-none">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="status" class="flex-1 h-9 bg-surface-container-lowest border border-outline rounded-xl px-3 text-xs text-on-surface focus:border-primary outline-none">
                <option value="">Semua status</option>
                <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
            </select>
            <button type="submit" class="h-9 px-4 bg-primary text-on-primary text-xs font-semibold rounded-xl shrink-0">Cari</button>
        </div>
    </form>

    <!-- Info Banner -->
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 flex items-start gap-2.5">
        <span class="material-symbols-outlined text-amber-500 text-[18px] shrink-0 mt-0.5">info</span>
        <p class="text-xs text-amber-900 leading-relaxed">Saldo diperbarui otomatis setiap 10 detik. Detail batch dan kedaluwarsa menyusul.</p>
    </div>

    <!-- Product Cards -->
    @if($products->isEmpty())
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <span class="material-symbols-outlined text-[40px] text-secondary/50 block mb-2">inventory_2</span>
            <p class="font-semibold text-on-surface text-sm">Produk tidak ditemukan</p>
            <p class="text-xs text-secondary mt-1">Coba ubah kata kunci atau filter pencarian.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($products as $product)
            <a href="{{ route('inventory.stock.show', $product) }}"
               class="bg-surface-container-lowest rounded-2xl border border-outline p-3.5 flex items-center gap-3.5 shadow-xs active:bg-surface-container-low transition-colors">
                <img src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder.svg') }}" alt="" class="w-11 h-11 rounded-xl object-cover shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold text-on-surface truncate">{{ $product->name }}</p>
                        <span @class([
                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0',
                            'bg-tertiary-fixed text-on-tertiary-fixed' => $product->is_active,
                            'bg-surface-container-highest text-secondary' => !$product->is_active,
                        ])>
                            <span @class(['w-1.5 h-1.5 rounded-full', 'bg-tertiary' => $product->is_active, 'bg-secondary' => !$product->is_active])></span>
                            {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="font-mono text-secondary text-[11px] mt-0.5 truncate">{{ $product->sku }}</p>
                    <div class="flex items-center gap-2 mt-1.5 text-[11px] text-secondary">
                        <span class="truncate">{{ $product->category->name }}</span>
                        <span class="w-1 h-1 rounded-full bg-outline shrink-0"></span>
                        <span class="shrink-0">Satuan: {{ $product->unit->symbol }}</span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-primary">Saldo: <span data-stock-balance="{{ $product->id }}">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit->symbol }}</span></p>
                    <span data-low-stock-status="{{ $product->id }}" @class(['mt-1 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700' => !empty($lowStockByProduct[$product->id]), 'hidden' => empty($lowStockByProduct[$product->id])]) title="{{ !empty($lowStockByProduct[$product->id]) ? 'Lokasi: '.implode(', ', $lowStockByProduct[$product->id]) : '' }}">Stok rendah{{ !empty($lowStockByProduct[$product->id]) ? ' · '.implode(', ', $lowStockByProduct[$product->id]) : '' }}</span>
                </div>
                <span class="material-symbols-outlined text-secondary text-[18px] shrink-0">chevron_right</span>
            </a>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
        <div class="pt-2">{{ $products->links() }}</div>
        @endif
    @endif

</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')

    <!-- Page Header Row -->
    <div class="flex items-center justify-between gap-4 min-w-0">
        <div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">Data Stock</h1>
            <p class="text-xs text-gray-500 mt-0.5">Saldo stok dari ledger · <span data-stock-live-updated>Memuat pembaruan stok…</span></p>
        </div>
        @if(auth()->user()->hasRole('super_admin'))
            <a href="{{ route('admin.master-data.create', 'products') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-[#c01230] transition-colors shrink-0">
                <span class="material-symbols-outlined text-[18px]">add</span>Tambah Produk
            </a>
        @endif
    </div>

    <!-- Info Banner -->
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center gap-3 text-sm text-amber-900">
        <span class="material-symbols-outlined text-amber-500 text-[18px] shrink-0">info</span>
        <span>Saldo dihitung dari transaksi stok dan diperbarui otomatis setiap 10 detik. Rincian batch serta kedaluwarsa menyusul.</span>
    </div>

    <!-- Main Card -->
    <section class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden min-w-0">

        <!-- Filter Bar -->
        <div class="px-6 py-4 border-b border-[#EAEAEA]">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Cari nama atau SKU..."
                        class="w-full pl-8 pr-4 h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all">
                </div>
                <!-- Category -->
                <select name="category" class="h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <!-- Status -->
                <select name="status" class="h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                </select>
                <!-- Sort -->
                <select name="sort" class="h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    <option value="name"       @selected(($filters['sort'] ?? 'name') === 'name')>Urut: Nama</option>
                    <option value="sku"        @selected(($filters['sort'] ?? '') === 'sku')>Urut: SKU</option>
                    <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>Urut: Terbaru</option>
                </select>
                <button type="submit" class="h-9 px-4 bg-[#DC143C] text-white text-xs font-semibold rounded-lg hover:bg-[#c01230] transition-colors shrink-0">
                    Terapkan
                </button>
                @if(array_filter($filters))
                    <a href="{{ route('inventory.stock.index') }}" class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-700 flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">close</span>Reset
                    </a>
                @endif
                <!-- Result count -->
                <span class="ml-auto text-xs text-gray-400 font-medium">{{ $products->total() }} produk</span>
            </form>
        </div>

        <!-- Table -->
        @if($products->isEmpty())
            <div class="py-16 text-center">
                <span class="material-symbols-outlined text-[48px] text-gray-300 block mb-3">inventory_2</span>
                <p class="font-semibold text-gray-700">Produk tidak ditemukan</p>
                <p class="mt-1 text-sm text-gray-400">Coba ubah kata kunci atau filter pencarian.</p>
                @if(array_filter($filters))
                    <a href="{{ route('inventory.stock.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#DC143C] hover:underline">
                        <span class="material-symbols-outlined text-[16px]">refresh</span>Reset filter
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[640px]" id="stock-table">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4 w-10 text-center">
                                <input type="checkbox" id="s-select-all" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C]">
                            </th>
                            <th class="py-3 px-3">
                                <a href="{{ route('inventory.stock.index', array_merge($filters, ['sort' => 'sku', 'direction' => (($filters['sort'] ?? '') === 'sku' && ($filters['direction'] ?? 'asc') === 'asc') ? 'desc' : 'asc'])) }}"
                                   class="flex items-center gap-1 hover:text-gray-800 transition-colors">
                                    SKU Code
                                    <span class="material-symbols-outlined text-[14px]">{{ ($filters['sort'] ?? '') === 'sku' ? (($filters['direction'] ?? 'asc') === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                                </a>
                            </th>
                            <th class="py-3 px-3">
                                <a href="{{ route('inventory.stock.index', array_merge($filters, ['sort' => 'name', 'direction' => (($filters['sort'] ?? 'name') === 'name' && ($filters['direction'] ?? 'asc') === 'asc') ? 'desc' : 'asc'])) }}"
                                   class="flex items-center gap-1 hover:text-gray-800 transition-colors">
                                    Produk
                                    <span class="material-symbols-outlined text-[14px]">{{ ($filters['sort'] ?? 'name') === 'name' ? (($filters['direction'] ?? 'asc') === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}</span>
                                </a>
                            </th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Satuan</th>
                            <th class="py-3 px-3">Saldo Stok</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA] text-xs" id="stock-table-body">
                        @foreach($products as $product)
                        <tr class="s-row hover:bg-gray-50/70 transition-colors">
                            <td class="py-3.5 px-4 text-center">
                                <input type="checkbox" class="s-row-check w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C]">
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="font-mono text-xs font-medium text-gray-600 bg-gray-100 px-2 py-0.5 rounded">{{ $product->sku }}</span>
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $product->image_path ? asset('storage/'.$product->image_path) : asset('images/product-placeholder.svg') }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0">
                                    <div>
                                        <div class="font-semibold text-gray-900 leading-tight">{{ $product->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-gray-600">{{ $product->category->name }}</td>
                            <td class="py-3.5 px-3 text-gray-500 font-mono">{{ $product->unit->symbol }}</td>
                            <td class="py-3.5 px-3">
                                <span class="font-mono text-xs font-semibold text-gray-700" data-stock-balance="{{ $product->id }}">{{ \App\Support\QuantityFormatter::format($product->current_stock) }} {{ $product->unit->symbol }}</span>
                            </td>
                            <td class="py-3.5 px-3">
                                @if($product->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Nonaktif
                                    </span>
                                @endif
                                <span data-low-stock-status="{{ $product->id }}" @class(['ml-1 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700' => !empty($lowStockByProduct[$product->id]), 'hidden' => empty($lowStockByProduct[$product->id])]) title="{{ !empty($lowStockByProduct[$product->id]) ? 'Lokasi: '.implode(', ', $lowStockByProduct[$product->id]) : '' }}">Stok rendah</span>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <a href="{{ route('inventory.stock.show', $product) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#FFF1F0] text-[#DC143C] text-[11px] font-semibold hover:bg-[#FFE4E1] transition-colors">
                                    <span class="material-symbols-outlined text-[13px]">open_in_new</span>Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-[#EAEAEA] flex items-center justify-between gap-4 text-xs text-gray-500">
                <div>
                    Menampilkan <span class="font-semibold text-gray-700">{{ $products->firstItem() }}–{{ $products->lastItem() }}</span>
                    dari <span class="font-semibold text-gray-700">{{ $products->total() }}</span> produk
                </div>
                <div>{{ $products->links() }}</div>
            </div>
        @endif

    </section>

@endsection

{{-- ======================================================================= --}}
{{-- PAGE SCRIPTS                                                             --}}
{{-- ======================================================================= --}}
@section('page-scripts')
    // Refresh balances for the current product page without resetting active filters.
    (function () {
        const endpoint = @js(route('inventory.stock.live')) + window.location.search;
        async function refreshBalances() {
            try {
                const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                data.items.forEach(item => {
                    document.querySelectorAll(`[data-stock-balance="${item.id}"]`).forEach(node => {
                        node.textContent = `${Number(item.quantity).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 })} ${item.unit}`;
                    });
                    document.querySelectorAll(`[data-low-stock-status="${item.id}"]`).forEach(node => {
                        const locations = item.low_stock_locations || [];
                        node.classList.toggle('hidden', locations.length === 0);
                        node.textContent = locations.length ? `Stok rendah${node.closest('a') ? ` · ${locations.join(', ')}` : ''}` : '';
                        node.title = locations.length ? `Lokasi: ${locations.join(', ')}` : '';
                    });
                });
                document.querySelectorAll('[data-stock-live-updated]').forEach(node => {
                    node.textContent = `Live · ${new Date(data.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
                });
            } catch (_) { /* keep the last successful balances visible while offline */ }
        }
        refreshBalances();
        window.setInterval(refreshBalances, 10000);
    })();

    // Navbar search → filter stock table rows
    function filterStockTable(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('#stock-table-body .s-row').forEach(row => {
            row.style.display = (q === '' || row.innerText.toLowerCase().includes(q)) ? '' : 'none';
        });
    }
    // Select all
    const sSelectAll = document.getElementById('s-select-all');
    if (sSelectAll) sSelectAll.addEventListener('change', () => {
        document.querySelectorAll('.s-row-check').forEach(cb => cb.checked = sSelectAll.checked);
    });
@endsection
