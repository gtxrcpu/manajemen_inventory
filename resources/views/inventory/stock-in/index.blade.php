@extends('layouts.dashboard')

@php $pageTitle = ($canApprove && ! $canManage ? 'Persetujuan Stock In' : 'Riwayat Stock In').' — Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full p-4 space-y-4" @if($canApprove && ! $canManage) data-pic-auto-refresh-root="mobile" @endif>
    <!-- Mobile Header -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-primary uppercase tracking-wider">
                <span class="material-symbols-outlined text-[14px]">move_to_inbox</span>
                <span>Inbound Stock</span>
            </div>
            <h1 class="font-poppins text-xl font-semibold text-on-surface">{{ $canApprove && ! $canManage ? 'Persetujuan Stock In' : 'Penerimaan Barang' }}</h1>
            <p class="text-xs text-secondary mt-0.5">{{ $receipts->total() }} transaksi barang masuk tercatat</p>
            @if($canApprove && ! $canManage)<p class="mt-1 text-[10px] font-medium text-emerald-700">● Pembaruan otomatis setiap 15 detik</p>@endif
        </div>
        @if($canManage)<a href="{{ route('inventory.stock-in.create') }}"
           class="h-9 px-3.5 bg-primary text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 shadow-sm active:scale-95 transition-transform shrink-0">
            <span class="material-symbols-outlined text-[16px]">add</span>Catat
        </a>@endif
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
                {{ number_format($stats['total_receipts'] ?? $receipts->total()) }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Penerimaan tercatat</p>
        </div>

        <div class="bg-white border border-[#EAEAEA] rounded-2xl p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total Kuantiti</span>
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">inventory_2</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-emerald-700 truncate">
                {{ \App\Support\QuantityFormatter::format($stats['total_items_qty'] ?? 0) }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Unit barang masuk</p>
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
                <span class="text-[10px] font-semibold uppercase tracking-wider">Vendor / Mitra</span>
                <span class="material-symbols-outlined text-amber-600 text-[18px]">local_shipping</span>
            </div>
            <div class="mt-1 font-poppins text-lg font-semibold text-on-surface">
                {{ $stats['suppliers_count'] ?? 0 }}
            </div>
            <p class="text-[10px] text-secondary mt-0.5">Supplier aktif</p>
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
        <form method="GET" action="{{ route('inventory.stock-in.index') }}" data-auto-filter class="p-3.5 pt-0 border-t border-outline/50 space-y-2.5">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </span>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="No. referensi, vendor, produk..."
                       class="w-full pl-9 pr-3 h-10 bg-surface-container-lowest border border-outline rounded-xl text-xs text-on-surface outline-none focus:border-primary">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <select name="location_id" class="h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi</option>@endunless
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filters['location_id'] ?? '') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>

                <select name="supplier_id" class="h-9 bg-surface-container-lowest border border-outline rounded-xl px-2.5 text-xs text-on-surface outline-none focus:border-primary">
                    <option value="">Semua Vendor</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected(($filters['supplier_id'] ?? '') == $sup->id)>{{ $sup->name }}</option>
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

            <div class="flex justify-end pt-1">
                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.stock-in.index') }}" class="h-9 px-3 border border-outline text-secondary text-xs font-semibold rounded-xl flex items-center justify-center">Reset</a>
                @endif
            </div>
        </form>
    </details>

    <!-- Mobile Receipts List -->
    @if($receipts->isEmpty())
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center mx-auto text-secondary mb-3">
                <span class="material-symbols-outlined text-[24px]">move_to_inbox</span>
            </div>
            <p class="font-semibold text-on-surface text-sm">Belum Ada Penerimaan Barang</p>
            <p class="text-xs text-secondary mt-1">Data penerimaan akan muncul setelah ada transaksi Stock In baru.</p>
            @if($canManage)<a href="{{ route('inventory.stock-in.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-semibold rounded-xl">
                <span class="material-symbols-outlined text-[16px]">add</span>Catat Sekarang
            </a>@endif
        </div>
    @else
        <div class="space-y-3">
            @foreach($receipts as $receipt)
                @php
                    $totalQty = $receipt->items->sum('quantity');
                    $productSample = $receipt->items->take(2);
                @endphp
                <a href="{{ route('inventory.stock-in.show', $receipt) }}"
                   class="block rounded-2xl border border-outline bg-white p-4 shadow-2xs active:bg-surface-container-low transition-colors">
                    <!-- Top Row: Ref & Status -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-md inline-block">
                                {{ $receipt->reference }}
                            </span>
                            <div class="flex items-center gap-1.5 text-[11px] text-secondary mt-1">
                                <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                <span>{{ $receipt->received_at->format('d M Y') }}</span>
                            </div>
                        </div>
                        @include('inventory.stock-in._status-badge', ['receipt' => $receipt])
                    </div>

                    <!-- Meta: Source & Location -->
                    <div class="mt-2.5 pt-2.5 border-t border-outline/50 flex flex-wrap items-center gap-2 text-xs">
                        <div class="flex items-center gap-1 text-on-surface font-medium">
                            <span class="material-symbols-outlined text-secondary text-[15px]">storefront</span>
                            <span class="truncate max-w-[140px]">{{ $receipt->supplier ? $receipt->supplier->name : $receipt->source }}</span>
                        </div>
                        <span class="text-secondary/40">•</span>
                        <div class="flex items-center gap-1 text-secondary">
                            <span class="material-symbols-outlined text-secondary text-[15px]">location_on</span>
                            <span class="truncate max-w-[120px]">{{ $receipt->location->name }}</span>
                        </div>
                    </div>

                    <!-- Items Preview Chips -->
                    <div class="mt-2 bg-surface-container-low rounded-xl p-2.5 text-xs space-y-1">
                        <div class="flex items-center justify-between text-[11px] text-secondary font-medium">
                            <span>{{ $receipt->items->count() }} Jenis Produk</span>
                            <span class="font-semibold text-primary">Total: {{ \App\Support\QuantityFormatter::format($totalQty) }} unit</span>
                        </div>
                        <div class="flex flex-wrap gap-1 pt-0.5">
                            @foreach($productSample as $item)
                                <span class="bg-white border border-outline px-2 py-0.5 rounded-md text-[10px] text-on-surface truncate max-w-[160px]">
                                    {{ $item->product->name }}: <b>{{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}</b>
                                </span>
                            @endforeach
                            @if($receipt->items->count() > 2)
                                <span class="bg-white border border-outline px-1.5 py-0.5 rounded-md text-[10px] text-secondary font-medium">
                                    +{{ $receipt->items->count() - 2 }} lainnya
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Footer: Receiver -->
                    <div class="mt-2.5 flex items-center justify-between text-[11px] text-secondary">
                        <div class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-surface-container flex items-center justify-center font-semibold text-[10px] text-primary">
                                {{ strtoupper(substr($receipt->receiver->name ?? 'U', 0, 1)) }}
                            </span>
                            <span>Diterima oleh {{ $receipt->receiver->name }}</span>
                        </div>
                        <span class="material-symbols-outlined text-secondary text-[16px]">chevron_right</span>
                    </div>
                </a>
            @endforeach
        </div>

        @if($receipts->hasPages())
            <div class="pt-2">{{ $receipts->links() }}</div>
        @endif
    @endif
</main>
@endsection


{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT (>= lg)                                                 --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<section class="space-y-6" @if($canApprove && ! $canManage) data-pic-auto-refresh-root="desktop" @endif>

    <!-- Header Row -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#DC143C] uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-[16px]">move_to_inbox</span>
                <span>Manajemen Penerimaan Stok</span>
            </div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">{{ $canApprove && ! $canManage ? 'Persetujuan Stock In' : 'Penerimaan Barang (Stock In)' }}</h1>
            <p class="text-xs text-gray-500 mt-1">
                {{ $isWarehouseStaff ? 'Penerimaan supplier dicatat melalui Purchase Order agar stok dan dokumen tetap mengikuti alur yang disetujui.' : 'Catatan dan riwayat dokumen penerimaan barang masuk ke gudang dari vendor, supplier, atau sumber lainnya.' }}
            </p>
            @if($canApprove && ! $canManage)<p class="mt-1 text-xs font-medium text-emerald-700">● Pembaruan otomatis setiap 15 detik</p>@endif
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('inventory.stock.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-xl shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span>Lihat Data Stock</span>
            </a>
            @if($canManage && $isWarehouseStaff)
                <a href="{{ route('inventory.stock-in.purchase-orders') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] hover:bg-[#b1002c] text-white text-xs font-semibold rounded-xl shadow-xs transition-all active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">fact_check</span>
                    <span>Validasi Penerimaan PO</span>
                </a>
            @elseif($canManage)<a href="{{ route('inventory.stock-in.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] hover:bg-[#b1002c] text-white text-xs font-semibold rounded-xl shadow-xs transition-all active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Catat Stock In Baru</span>
            </a>@endif
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
                {{ number_format($stats['total_receipts'] ?? $receipts->total()) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                <span>Dokumen penerimaan terdaftar</span>
            </div>
        </div>

        <!-- Metric 2: Total Kuantiti Masuk -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Volume Masuk</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-emerald-700 leading-tight truncate">
                {{ \App\Support\QuantityFormatter::format($stats['total_items_qty'] ?? 0) }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-emerald-600">
                <span class="material-symbols-outlined text-[14px]">arrow_downward</span>
                <span>Akumulasi kuantiti dari seluruh batch</span>
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
                <span>Penerimaan per {{ now()->translatedFormat('F Y') }}</span>
            </div>
        </div>

        <!-- Metric 4: Vendor / Supplier Terhubung -->
        <div class="bg-white border border-[#EAEAEA] rounded-xl p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] hover:shadow-[0_6px_18px_rgba(0,0,0,0.06)] transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Supplier Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                </div>
            </div>
            <div class="mt-2 font-poppins text-2xl font-semibold text-gray-900 leading-tight">
                {{ $stats['suppliers_count'] ?? 0 }}
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-400">
                <span>Mitra pemasok operasional</span>
            </div>
        </div>
    </div>

    <!-- Main Table Card with Toolbar -->
    <div class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.04)] overflow-hidden">

        <!-- Filter Bar -->
        <div class="p-4 border-b border-[#EAEAEA] bg-[#FDFCFB]">
            <form method="GET" action="{{ route('inventory.stock-in.index') }}" data-auto-filter class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[220px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <span class="material-symbols-outlined text-[18px]">search</span>
                    </div>
                    <input name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Cari no. referensi, vendor, nama produk, SKU..."
                           class="w-full pl-9 pr-4 h-9 bg-white border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all">
                </div>

                <!-- Location Filter -->
                <select name="location_id" class="h-9 bg-white border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    @unless(auth()->user()->isLocationScopedUser())<option value="">Semua Lokasi Gudang</option>@endunless
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(($filters['location_id'] ?? '') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>

                <!-- Supplier Filter -->
                <select name="supplier_id" class="h-9 bg-white border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all cursor-pointer">
                    <option value="">Semua Vendor / Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" @selected(($filters['supplier_id'] ?? '') == $sup->id)>{{ $sup->name }}</option>
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
                @if(array_filter($filters ?? []))
                    <a href="{{ route('inventory.stock-in.index') }}" class="h-9 px-3 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-600 text-xs font-medium rounded-lg flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">close</span>Reset
                    </a>
                @endif

                <!-- Total Count -->
                <div class="ml-auto text-xs text-gray-500 font-medium">
                    Menampilkan <span class="font-semibold text-gray-800">{{ $receipts->count() }}</span> dari <span class="font-semibold text-gray-800">{{ $receipts->total() }}</span> data
                </div>
            </form>
        </div>

        <!-- Table View -->
        @if($receipts->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto text-gray-400 mb-3">
                    <span class="material-symbols-outlined text-[28px]">move_to_inbox</span>
                </div>
                <h3 class="font-semibold text-gray-800 text-sm">Tidak Ada Transaksi Penerimaan</h3>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                    @if(array_filter($filters ?? []))
                        Tidak ada data penerimaan yang sesuai dengan kriteria filter pencarian Anda. Coba reset filter.
                    @else
                        Belum ada dokumen penerimaan barang masuk yang dicatat di sistem.
                    @endif
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    @if(array_filter($filters ?? []))
                        <a href="{{ route('inventory.stock-in.index') }}" class="px-3.5 py-1.5 border border-[#EAEAEA] bg-white text-xs font-semibold text-gray-700 rounded-lg hover:bg-gray-50">
                            Reset Filter
                        </a>
                    @endif
                    @if($canManage)<a href="{{ route('inventory.stock-in.create') }}" class="px-3.5 py-1.5 bg-[#DC143C] text-white text-xs font-semibold rounded-lg hover:bg-[#b1002c]">
                        + Catat Stock In
                    </a>@endif
                </div>
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-b border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">No. Referensi</th>
                            <th class="py-3 px-4">Tanggal Diterima</th>
                            <th class="py-3 px-4">Sumber / Supplier</th>
                            <th class="py-3 px-4">Lokasi Masuk</th>
                            <th class="py-3 px-4">Rincian Barang</th>
                            <th class="py-3 px-4">Petugas</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA]">
                        @foreach($receipts as $receipt)
                            @php
                                $totalQuantity = $receipt->items->sum('quantity');
                                $itemsCount = $receipt->items->count();
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <!-- No. Referensi -->
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('inventory.stock-in.show', $receipt) }}"
                                       class="font-mono font-semibold text-[#DC143C] hover:underline flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-[#DC143C]">receipt</span>
                                        <span>{{ $receipt->reference }}</span>
                                    </a>
                                    @if($receipt->notes)
                                        <p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1 italic max-w-xs">
                                            "{{ $receipt->notes }}"
                                        </p>
                                    @endif
                                </td>

                                <!-- Tanggal Diterima -->
                                <td class="py-3.5 px-4 text-gray-700 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $receipt->received_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $receipt->received_at->diffForHumans() }}</div>
                                </td>

                                <!-- Sumber / Supplier -->
                                <td class="py-3.5 px-4">
                                    @if($receipt->supplier)
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[15px] text-gray-400">storefront</span>
                                            <span class="font-semibold text-gray-900 truncate max-w-[160px]">{{ $receipt->supplier->name }}</span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Sumber: {{ $receipt->source }}</span>
                                    @else
                                        <div class="flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[15px] text-gray-400">source</span>
                                            <span class="font-medium text-gray-800">{{ $receipt->source }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Lokasi -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-medium text-[11px]">
                                        <span class="material-symbols-outlined text-[14px] text-gray-500">warehouse</span>
                                        <span>{{ $receipt->location->name }}</span>
                                    </div>
                                </td>

                                <!-- Rincian Barang -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold text-[11px]">
                                            {{ $itemsCount }} Produk
                                        </span>
                                        <span class="font-semibold text-gray-900 text-xs">
                                            {{ \App\Support\QuantityFormatter::format($totalQuantity) }} unit
                                        </span>
                                    </div>
                                    <!-- Items preview list -->
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($receipt->items->take(2) as $item)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-gray-50 border border-gray-200 text-[10px] text-gray-600 truncate max-w-[180px]">
                                                <span>{{ $item->product->name }}</span>
                                                <b class="text-gray-900">({{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }})</b>
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
                                        <div class="w-6 h-6 rounded-full bg-[#FFF1F0] text-[#DC143C] font-semibold text-[10px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($receipt->receiver->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <span class="text-gray-800 font-medium">{{ $receipt->receiver->name }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @include('inventory.stock-in._status-badge', ['receipt' => $receipt])
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('inventory.stock-in.show', $receipt) }}"
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
            @if($receipts->hasPages())
                <div class="p-4 border-t border-[#EAEAEA] bg-[#FDFCFB]">
                    {{ $receipts->links() }}
                </div>
            @endif
        @endif

    </div>

</section>
@endsection

@if($canApprove && ! $canManage)
<script>
    (() => {
        let refreshing = false;

        async function refreshPicReceipts() {
            const active = document.activeElement;
            if (document.hidden || refreshing || document.querySelector('details[open]')
                || active?.matches('input, select, textarea, button')) return;

            refreshing = true;
            const scrollY = window.scrollY;

            try {
                const response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) return;

                const refreshedPage = new DOMParser().parseFromString(await response.text(), 'text/html');
                for (const layout of ['mobile', 'desktop']) {
                    const current = document.querySelector(`[data-pic-auto-refresh-root="${layout}"]`);
                    const updated = refreshedPage.querySelector(`[data-pic-auto-refresh-root="${layout}"]`);
                    if (!current || !updated) return;
                    current.innerHTML = updated.innerHTML;
                }

                window.scrollTo({ top: scrollY, behavior: 'auto' });
            } catch (error) {
                console.warn('Pembaruan daftar Stock In gagal; akan dicoba kembali.', error);
            } finally {
                refreshing = false;
            }
        }

        window.setInterval(refreshPicReceipts, 15000);
    })();
</script>
@endif
