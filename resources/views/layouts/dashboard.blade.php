@extends('layouts.app')

@section('title', $pageTitle ?? 'Technolife - Inventory')

@section('content')
<div class="min-h-screen bg-surface font-inter text-on-surface antialiased selection:bg-primary-container selection:text-primary">
    @php
        $navbarUser = auth()->user();
        $isManagementReportUser = $navbarUser->hasRole('management') && ! $navbarUser->hasRole('super_admin');
        $navbarScopedLocationIds = $navbarUser->isLocationScopedUser() ? $navbarUser->assignedLocationIds() : [];
        $navbarIsWarehouseStaff = $navbarUser->isWarehouseStaff() && ! $navbarUser->hasRole('super_admin');
        $navbarLocations = $navbarUser->isLocationScopedUser()
            ? \App\Models\Location::whereIn('id', $navbarScopedLocationIds)->where('is_active', true)->orderBy('name')->get()
            : \App\Models\Location::where('is_active', true)->orderBy('name')->get();
        $navbarSelectedLocationId = $navbarUser->isLocationScopedUser()
            ? (in_array((int) $navbarUser->location_id, $navbarScopedLocationIds, true) ? (int) $navbarUser->location_id : ($navbarScopedLocationIds[0] ?? null))
            : session('selected_location_id');
        $navbarSelectedLocation = $navbarLocations->firstWhere('id', $navbarSelectedLocationId);
        $navbarLocationName = $navbarSelectedLocation?->name ?? ($navbarIsWarehouseStaff ? 'Gudang belum ditetapkan' : ($navbarUser->isLocationScopedUser() ? 'Lokasi belum ditetapkan' : 'Semua Lokasi'));
        $unreadLowStockCount = $navbarUser->hasPermissionTo('low_stock.view')
            ? $navbarUser->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->count()
            : 0;
        $navbarNotifications = $navbarUser->hasPermissionTo('low_stock.view')
            ? $navbarUser->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->latest()->limit(5)->get()
            : collect();
    @endphp

    {{-- ================================================================ --}}
    {{-- MOBILE VIEW  (< lg)                                              --}}
    {{-- ================================================================ --}}
    <div class="block lg:hidden min-h-screen flex flex-col">

        <!-- Mobile Header (Fixed) -->
        <header class="fixed top-0 w-full z-50 pt-safe bg-surface/85 backdrop-blur-xl border-b border-outline/60 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">
            <div class="h-16 px-4 flex items-center justify-between gap-2">
                <a href="{{ route('dashboard') }}" aria-label="Technolife" class="flex items-center min-w-0 shrink-0">
                    <img src="{{ asset('logo.webp') }}" alt="Technolife" class="h-14 w-14 shrink-0 object-contain">
                </a>
                @if(!$navbarUser->isLocationScopedUser())
                <details class="relative shrink min-w-0">
                    <summary aria-label="Pilih lokasi" class="list-none cursor-pointer min-h-[40px] max-w-[130px] px-2 py-1.5 rounded-full bg-surface-container flex items-center gap-1 active:bg-surface-variant">
                        <span class="material-symbols-outlined text-primary text-[18px]">location_on</span>
                        <span class="text-xs font-semibold text-on-surface truncate">{{ $navbarLocationName }}</span>
                        <span class="material-symbols-outlined text-secondary text-[16px]">arrow_drop_down</span>
                    </summary>
                    <div class="absolute left-0 top-11 z-[80] w-56 rounded-xl border border-outline bg-white p-2 text-on-surface shadow-xl">
                        <form method="POST" action="{{ route('location-context.update') }}">@csrf<input type="hidden" name="location_id" value=""><button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-surface-container-low">{{ !$navbarSelectedLocationId ? '✓ ' : '' }}Semua Lokasi</button></form>
                        @foreach($navbarLocations as $navbarLocation)
                            <form method="POST" action="{{ route('location-context.update') }}">@csrf<input type="hidden" name="location_id" value="{{ $navbarLocation->id }}"><button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-surface-container-low">{{ (string)$navbarSelectedLocationId === (string)$navbarLocation->id ? '✓ ' : '' }}{{ $navbarLocation->name }}</button></form>
                        @endforeach
                    </div>
                </details>
                @else
                    <span class="flex min-w-0 items-center gap-1 rounded-full bg-surface-container px-2 py-1.5 text-xs font-semibold"><span class="material-symbols-outlined text-primary text-[18px]">location_on</span><span class="truncate max-w-[76px]">{{ $navbarLocationName }}</span></span>
                @endif
                <a href="{{ route('search.index') }}" aria-label="Cari" class="min-h-[40px] min-w-[36px] flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">search</span>
                </a>
                @if(auth()->user()->hasPermissionTo('low_stock.view'))
                    <details class="relative">
                        <summary aria-label="Notifikasi ({{ $unreadLowStockCount }} belum dibaca)" class="relative list-none min-h-[40px] min-w-[36px] cursor-pointer flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined">notifications</span>
                            @if($unreadLowStockCount > 0)<span class="absolute -right-0.5 -top-0.5 min-w-4 h-4 px-1 rounded-full bg-primary text-white text-[9px] font-bold flex items-center justify-center">{{ $unreadLowStockCount > 9 ? '9+' : $unreadLowStockCount }}</span>@endif
                        </summary>
                        <div class="absolute right-0 top-11 z-[80] w-72 rounded-xl border border-outline bg-white p-3 text-on-surface shadow-xl">
                            <div class="mb-2 flex items-center justify-between"><b class="text-sm">Notifikasi</b><span class="text-[11px] text-secondary">{{ $unreadLowStockCount }} belum dibaca</span></div>
                            @forelse($navbarNotifications as $notification)
                                <a href="{{ route('low-stock.index') }}" class="block rounded-lg border-t border-outline px-2 py-2.5 hover:bg-surface-container-low"><b class="block text-xs">{{ $notification->data['product'] ?? 'Peringatan stok' }}</b><span class="mt-0.5 block text-[11px] text-secondary">{{ $notification->data['location'] ?? '' }} · {{ $notification->data['message'] ?? 'Stok mencapai batas minimum.' }}</span></a>
                            @empty
                                <p class="border-t border-outline py-4 text-center text-xs text-secondary">Tidak ada notifikasi baru.</p>
                            @endforelse
                            <a href="{{ route('low-stock.index') }}" class="mt-2 block rounded-lg bg-surface-container-low px-3 py-2 text-center text-xs font-semibold text-primary">Lihat semua alert stok</a>
                        </div>
                    </details>
                @endif
                <details data-close-on-outside class="relative shrink-0">
                    <summary aria-label="Menu profil" class="flex min-h-[40px] cursor-pointer list-none items-center gap-0.5 px-1">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-on-primary">
                            <span class="material-symbols-outlined text-[18px]">person</span>
                        </span>
                        <span class="material-symbols-outlined text-secondary text-[16px]">arrow_drop_down</span>
                    </summary>
                    <div class="absolute right-0 top-11 z-[70] w-52 rounded-xl border border-outline bg-white p-2 text-on-surface shadow-xl">
                        <p class="truncate px-3 py-2 text-xs font-semibold text-secondary">{{ auth()->user()->name }}</p>
                        <a href="{{ route('account') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-surface-container-low">Profil &amp; Pengaturan</a>
                        @if(auth()->user()->hasPermissionTo('users.manage'))
                            <a href="{{ route('admin.roles.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-surface-container-low">Role &amp; Permission</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-primary hover:bg-surface-container-low">Keluar</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <!-- Mobile Drawer Overlay -->
        <div id="m-drawer-overlay" onclick="closeMobileDrawer()" class="fixed inset-0 z-[55] bg-black/40 backdrop-blur-xs hidden" aria-hidden="true"></div>

        <!-- Mobile Drawer -->
        <nav id="m-drawer" aria-label="Menu navigasi" class="fixed left-0 top-0 z-[60] h-full w-72 bg-white shadow-2xl flex flex-col -translate-x-full transition-transform duration-300 ease-in-out">
            <div class="grid grid-cols-3 items-center px-4 pt-safe pb-0 h-16 border-b border-outline/50 shrink-0">
                <div></div>
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center">
                    <img src="{{ asset('logo.webp') }}" alt="Technolife" class="h-12 w-12 shrink-0 object-contain">
                </a>
                <div class="flex justify-end">
                    <button onclick="closeMobileDrawer()" aria-label="Tutup menu" class="w-9 h-9 flex items-center justify-center rounded-xl text-secondary hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-[22px]">close</span>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
                <p class="px-3 pb-2 pt-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Menu Utama</p>
                <a href="{{ route('dashboard') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('dashboard'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('dashboard')])>
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>Dashboard
                </a>
                @unless($isManagementReportUser)
                <a href="{{ route('inventory.stock.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.stock.*')])>
                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>Data Stock
                </a>
                @if(auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-in.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-in.*') && !request()->routeIs('inventory.stock-in.purchase-orders'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !(request()->routeIs('inventory.stock-in.*') && !request()->routeIs('inventory.stock-in.purchase-orders'))])><span class="material-symbols-outlined text-[20px]">move_to_inbox</span>Stock In</a>@elseif(auth()->user()->hasPermissionTo('stock-in.approve'))<a href="{{ route('inventory.stock-in.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-in.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.stock-in.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>Persetujuan Stock In</a>@endif
                @if((auth()->user()->hasRole('staff_gudang') || auth()->user()->hasRole('staff_outlet')) && auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-in.purchase-orders') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-in.purchase-orders') || request()->routeIs('purchasing.orders.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !(request()->routeIs('inventory.stock-in.purchase-orders') || request()->routeIs('purchasing.orders.*'))])><span class="material-symbols-outlined text-[20px]">fact_check</span>Validasi Penerimaan</a>@endif
                @if(auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-out.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-out.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.stock-out.*')])><span class="material-symbols-outlined text-[20px]">outbox</span>Stock Out</a>@endif
                @if(auth()->user()->hasPermissionTo('transfer.manage') || auth()->user()->hasPermissionTo('transfer.receive') || auth()->user()->hasPermissionTo('transfer.approve'))<a href="{{ route('inventory.transfers.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.transfers.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.transfers.*')])><span class="material-symbols-outlined text-[20px]">local_shipping</span>Distribusi</a>@endif
                @if(auth()->user()->hasPermissionTo('purchasing.manage'))<a href="{{ route('purchasing.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('purchasing.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('purchasing.*')])><span class="material-symbols-outlined text-[20px]">shopping_cart</span>Pengadaan</a>@endif
                @if(auth()->user()->hasPermissionTo('invoice.view') || auth()->user()->hasPermissionTo('invoice.review'))<a href="{{ route('purchase-invoices.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('purchase-invoices.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('purchase-invoices.*')])><span class="material-symbols-outlined text-[20px]">receipt_long</span>Faktur Pembelian</a>@endif
                @if(auth()->user()->hasPermissionTo('opname.view'))<a href="{{ route('inventory.stock-opname.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-opname.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.stock-opname.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>{{ auth()->user()->hasRole('purchasing') ? 'Riwayat Opname' : 'Stock Opname' }}</a>@endif
                @if(auth()->user()->hasPermissionTo('adjust.manage'))<a href="{{ route('inventory.stock-adjustments.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.stock-adjustments.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.stock-adjustments.*')])><span class="material-symbols-outlined text-[20px]">tune</span>Stock Adjustment</a>@endif
                <a href="{{ route('inventory.transactions.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('inventory.movements.*', 'inventory.transactions.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('inventory.movements.*', 'inventory.transactions.*')])>
                    <span class="material-symbols-outlined text-[20px] text-secondary">receipt_long</span>Riwayat
                </a>
                @if($navbarUser->hasPermissionTo('reports.view'))
                    <a href="{{ route('reports.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('reports.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('reports.*')])>
                        <span class="material-symbols-outlined text-[20px] text-secondary">bar_chart</span>Laporan
                    </a>
                @endif
                @if(auth()->user()->hasPermissionTo('low_stock.view'))<a href="{{ route('low-stock.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('low-stock.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('low-stock.*')])><span class="material-symbols-outlined text-[20px]">notifications_active</span>Stok Minimum</a>@endif
                @if(auth()->user()->hasPermissionTo('low_stock.manage'))<a href="{{ route('low-stock.thresholds') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('low-stock.thresholds'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('low-stock.thresholds')])><span class="material-symbols-outlined text-[20px]">tune</span>Batas Minimum</a>@endif
                @if(auth()->user()->hasPermissionTo('whatsapp.manage'))<a href="{{ route('admin.whatsapp-alerts.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('admin.whatsapp-alerts.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('admin.whatsapp-alerts.*')])><span class="material-symbols-outlined text-[20px]">chat</span>Alert WhatsApp</a>@endif
                @endunless
                @if($navbarUser->hasPermissionTo('purchase-request.approve'))
                    <a href="{{ route('approvals.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('approvals.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('approvals.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>Persetujuan</a>
                @endif                @if(auth()->user()->hasPermissionTo('users.manage'))
                    <p class="px-3 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Administrasi</p>
                    <a href="{{ route('admin.users.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('admin.users.*') && !request()->routeIs('admin.roles.*')])>
                        <span class="material-symbols-outlined text-[20px]">group</span>Pengguna
                    </a>
                    <a href="{{ route('admin.master-data.index', 'products') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('admin.master-data.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('admin.master-data.*')])>
                        <span class="material-symbols-outlined text-[20px]">database</span>Master Data
                    </a>
                    <a href="{{ route('admin.settings.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('admin.settings.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('admin.settings.*')])>
                        <span class="material-symbols-outlined text-[20px]">settings</span>Pengaturan Sistem
                    </a>
                    <a href="{{ route('admin.audit.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors', 'bg-primary/10 font-semibold text-primary' => request()->routeIs('admin.audit.*'), 'font-medium text-gray-700 hover:bg-surface-container-low' => !request()->routeIs('admin.audit.*')])>
                        <span class="material-symbols-outlined text-[20px]">manage_search</span>Audit Aktivitas
                    </a>
                @endif
            </div>
            <div class="shrink-0 border-t border-outline/50 px-4 py-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-on-surface truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-secondary truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="w-full flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-primary hover:bg-primary/10 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">logout</span>Keluar
                    </button>
                </form>
            </div>
        </nav>

        <!-- Mobile Body -->
        <main class="flex-1 flex flex-col relative w-full pt-16 pb-24 bg-surface">
            @yield('mobile-content')
        </main>

        <!-- Mobile Bottom Nav -->
        <nav aria-label="Navigasi utama" class="fixed bottom-0 left-0 z-50 w-full border-t border-outline/70 bg-white/95 pb-safe shadow-[0_-1px_8px_rgba(0,0,0,0.06)] backdrop-blur-xl">
            @if($isManagementReportUser)
            <div class="flex h-16 items-center justify-around px-3">
                <a href="{{ route('dashboard') }}" @class(['flex min-h-12 min-w-[72px] flex-col items-center justify-center', 'text-primary' => request()->routeIs('dashboard'), 'text-secondary' => !request()->routeIs('dashboard')])><span class="material-symbols-outlined text-[22px]">home</span><span class="mt-0.5 text-[11px]">Beranda</span></a>
                @if($navbarUser->hasPermissionTo('reports.view'))
                    <a href="{{ route('reports.index') }}" @class(['flex min-h-12 min-w-[72px] flex-col items-center justify-center', 'text-primary' => request()->routeIs('reports.*'), 'text-secondary' => !request()->routeIs('reports.*')])><span class="material-symbols-outlined text-[22px]">bar_chart</span><span class="mt-0.5 text-[11px]">Laporan</span></a>
                @endif
                <a href="{{ route('finance.valuation') }}" @class(['flex min-h-12 min-w-[72px] flex-col items-center justify-center', 'text-primary' => request()->routeIs('finance.valuation*'), 'text-secondary' => !request()->routeIs('finance.valuation*')])><span class="material-symbols-outlined text-[22px]">calculate</span><span class="mt-0.5 text-[11px]">Valuasi</span></a>
                <button type="button" onclick="openMobileDrawer()" class="flex min-h-12 min-w-[72px] flex-col items-center justify-center text-secondary" aria-label="Buka semua menu"><span class="material-symbols-outlined text-[22px]">menu</span><span class="mt-0.5 text-[11px]">Menu</span></button>
            </div>
            @else
            <div class="relative flex h-16 items-center justify-around px-1">
                <a href="{{ route('dashboard') }}" @class(['relative flex min-h-12 min-w-[48px] flex-col items-center justify-center', 'text-primary' => request()->routeIs('dashboard'), 'text-secondary' => !request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    <span class="material-symbols-outlined text-[22px]">home</span>
                    <span class="mt-0.5 text-[11px] @if(request()->routeIs('dashboard')) font-semibold @endif">Beranda</span>
                    @if(request()->routeIs('dashboard'))<span class="mt-0.5 h-1 w-1 rounded-full bg-primary"></span>@endif
                </a>
                <a href="{{ route('inventory.stock.index') }}" @class(['flex min-h-12 min-w-[48px] flex-col items-center justify-center transition-colors', 'text-primary' => request()->routeIs('inventory.stock.*'), 'text-secondary hover:text-on-surface' => !request()->routeIs('inventory.stock.*')])>
                    <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                    <span class="mt-0.5 text-[11px] @if(request()->routeIs('inventory.stock.*')) font-semibold @endif">Data Stock</span>
                </a>
                <div class="relative -top-3 flex min-h-12 min-w-[48px] flex-col items-center justify-center">
                    @if(auth()->user()->hasPermissionTo('users.manage'))
                        <a href="{{ route('admin.users.index') }}" aria-label="Pengguna" class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary shadow-[0_4px_12px_rgba(177,0,44,0.35)] transition-transform active:scale-95">
                            <span class="material-symbols-outlined text-[26px]">group</span>
                        </a>
                        <span class="mt-0.5 text-[10px] font-semibold text-primary">Pengguna</span>
                    @else
                        <a href="{{ route('inventory.stock.index') }}" aria-label="Pantau stok" class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary shadow-[0_4px_12px_rgba(177,0,44,0.35)]">
                            <span class="material-symbols-outlined text-[26px]">monitoring</span>
                        </a>
                        <span class="mt-0.5 text-[10px] font-semibold text-primary">Pantau</span>
                    @endif
                </div>
                <a href="{{ route('inventory.transactions.index') }}" @class(['flex min-h-12 min-w-[48px] flex-col items-center justify-center transition-colors', 'text-primary' => request()->routeIs('inventory.movements.*', 'inventory.transactions.*'), 'text-secondary hover:text-on-surface' => !request()->routeIs('inventory.movements.*', 'inventory.transactions.*')])>
                    <span class="material-symbols-outlined text-[22px]">receipt_long</span>
                    <span class="mt-0.5 text-[11px] @if(request()->routeIs('inventory.movements.*', 'inventory.transactions.*')) font-semibold @endif">Riwayat</span>
                </a>
                <button type="button" onclick="openMobileDrawer()" class="flex min-h-12 min-w-[48px] flex-col items-center justify-center text-secondary transition-colors hover:text-on-surface" aria-label="Buka semua menu">
                    <span class="material-symbols-outlined text-[22px]">menu</span>
                    <span class="mt-0.5 text-[11px]">Lainnya</span>
                </button>
            </div>
            @endif
        </nav>
    </div>

    {{-- ================================================================ --}}
    {{-- DESKTOP VIEW  (>= lg)                                            --}}
    {{-- ================================================================ --}}
    <div class="hidden lg:block min-h-screen bg-[#FBF9F8]">

        <!-- Desktop Sidebar (fixed) -->
        <aside aria-label="Navigasi utama" class="hidden lg:flex fixed top-0 left-0 h-screen w-52 flex-col border-r border-[#EAEAEA] bg-white shadow-sm z-30">
            <!-- Logo -->
            <div class="h-20 flex items-center justify-center border-b border-[#EAEAEA] shrink-0">
                <a href="{{ route('dashboard') }}" aria-label="Technolife Dashboard" class="flex items-center justify-center">
                    <img src="{{ asset('logo.webp') }}" alt="Technolife" class="h-20 w-20 shrink-0 object-contain">
                </a>
            </div>
            <!-- Nav Links -->
            <div class="flex-1 overflow-y-auto px-3 py-5 space-y-0.5">
                <p class="px-3 pb-2 pt-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Menu Utama</p>
                <a href="{{ route('dashboard') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('dashboard'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('dashboard')])>
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>Dashboard
                </a>
                @unless($isManagementReportUser)
                <a href="{{ route('inventory.stock.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.stock.*')])>
                    <span class="material-symbols-outlined text-[20px]">inventory_2</span>Data Stock
                </a>
                @if(auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-in.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-in.*') && !request()->routeIs('inventory.stock-in.purchase-orders'), 'font-medium text-gray-600 hover:bg-gray-50' => !(request()->routeIs('inventory.stock-in.*') && !request()->routeIs('inventory.stock-in.purchase-orders'))])><span class="material-symbols-outlined text-[20px]">move_to_inbox</span>Stock In</a>@elseif(auth()->user()->hasPermissionTo('stock-in.approve'))<a href="{{ route('inventory.stock-in.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-in.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.stock-in.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>Persetujuan Stock In</a>@endif
                @if((auth()->user()->hasRole('staff_gudang') || auth()->user()->hasRole('staff_outlet')) && auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-in.purchase-orders') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-in.purchase-orders') || request()->routeIs('purchasing.orders.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !(request()->routeIs('inventory.stock-in.purchase-orders') || request()->routeIs('purchasing.orders.*'))])><span class="material-symbols-outlined text-[20px]">fact_check</span>Validasi Penerimaan</a>@endif
                @if(auth()->user()->hasPermissionTo('stock.manage'))<a href="{{ route('inventory.stock-out.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-out.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.stock-out.*')])><span class="material-symbols-outlined text-[20px]">outbox</span>Stock Out</a>@endif
                @if(auth()->user()->hasPermissionTo('transfer.manage') || auth()->user()->hasPermissionTo('transfer.receive') || auth()->user()->hasPermissionTo('transfer.approve'))<a href="{{ route('inventory.transfers.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.transfers.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.transfers.*')])><span class="material-symbols-outlined text-[20px]">local_shipping</span>Distribusi</a>@endif
                @if(auth()->user()->hasPermissionTo('purchasing.manage'))<a href="{{ route('purchasing.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('purchasing.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('purchasing.*')])><span class="material-symbols-outlined text-[20px]">shopping_cart</span>Pengadaan</a>@endif
                @if(auth()->user()->hasPermissionTo('invoice.view') || auth()->user()->hasPermissionTo('invoice.review'))<a href="{{ route('purchase-invoices.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('purchase-invoices.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('purchase-invoices.*')])><span class="material-symbols-outlined text-[20px]">receipt_long</span>Faktur Pembelian</a>@endif
                @if(auth()->user()->hasPermissionTo('opname.view'))<a href="{{ route('inventory.stock-opname.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-opname.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.stock-opname.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>{{ auth()->user()->hasRole('purchasing') ? 'Riwayat Opname' : 'Stock Opname' }}</a>@endif
                @if(auth()->user()->hasPermissionTo('adjust.manage'))<a href="{{ route('inventory.stock-adjustments.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.stock-adjustments.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.stock-adjustments.*')])><span class="material-symbols-outlined text-[20px]">tune</span>Stock Adjustment</a>@endif
                <a href="{{ route('inventory.transactions.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('inventory.movements.*', 'inventory.transactions.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('inventory.movements.*', 'inventory.transactions.*')])>
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>Riwayat
                </a>
                @if($navbarUser->hasPermissionTo('reports.view'))
                    <a href="{{ route('reports.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('reports.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('reports.*')])>
                        <span class="material-symbols-outlined text-[20px]">bar_chart</span>Laporan
                    </a>
                @endif
                @if(auth()->user()->hasPermissionTo('low_stock.view'))<a href="{{ route('low-stock.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('low-stock.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('low-stock.*')])><span class="material-symbols-outlined text-[20px]">notifications_active</span>Stok Minimum</a>@endif
                @if(auth()->user()->hasPermissionTo('low_stock.manage'))<a href="{{ route('low-stock.thresholds') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('low-stock.thresholds'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('low-stock.thresholds')])><span class="material-symbols-outlined text-[20px]">tune</span>Batas Minimum</a>@endif
                @if(auth()->user()->hasPermissionTo('whatsapp.manage'))<a href="{{ route('admin.whatsapp-alerts.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('admin.whatsapp-alerts.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('admin.whatsapp-alerts.*')])><span class="material-symbols-outlined text-[20px]">chat</span>Alert WhatsApp</a>@endif
                @endunless
                @if($navbarUser->hasPermissionTo('purchase-request.approve'))
                    <a href="{{ route('approvals.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('approvals.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('approvals.*')])><span class="material-symbols-outlined text-[20px]">fact_check</span>Persetujuan</a>
                @endif                @if(auth()->user()->hasPermissionTo('users.manage'))
                    <p class="px-3 pb-2 pt-5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Administrasi</p>
                    <a href="{{ route('admin.users.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('admin.users.*') && !request()->routeIs('admin.roles.*')])>
                        <span class="material-symbols-outlined text-[20px]">group</span>Pengguna
                    </a>
                    <a href="{{ route('admin.master-data.index', 'products') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('admin.master-data.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('admin.master-data.*')])>
                        <span class="material-symbols-outlined text-[20px]">database</span>Master Data
                    </a>
                    <a href="{{ route('admin.settings.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('admin.settings.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('admin.settings.*')])>
                        <span class="material-symbols-outlined text-[20px]">settings</span>Pengaturan Sistem
                    </a>
                    <a href="{{ route('admin.audit.index') }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors', 'bg-[#FFF1F0] font-semibold text-[#DC143C]' => request()->routeIs('admin.audit.*'), 'font-medium text-gray-600 hover:bg-gray-50' => !request()->routeIs('admin.audit.*')])>
                        <span class="material-symbols-outlined text-[20px]">manage_search</span>Audit Aktivitas
                    </a>
                @endif
            </div>
        </aside>

        <!-- Right Column (offset by sidebar width) -->
        <div class="ml-52 flex flex-col min-w-0 min-h-screen">

            <!-- Desktop Navbar -->
            <header class="fixed top-0 left-52 right-0 z-20 h-14 bg-white border-b border-[#EAEAEA] shadow-[0_1px_4px_rgba(0,0,0,0.02)]">
                <div class="h-full grid grid-cols-[200px_1fr_200px] items-center px-5 xl:px-6 gap-4">
                    <!-- Left: Location -->
                    <div class="flex items-center">
                        @if(!$navbarUser->isLocationScopedUser())
                        <details class="relative">
                            <summary aria-label="Pilih lokasi" class="flex list-none cursor-pointer items-center gap-1.5 px-2.5 py-1.5 bg-[#FBF9F8] hover:bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-700 transition-colors"><span class="material-symbols-outlined text-[16px] text-gray-500">location_on</span><span class="truncate max-w-[110px]">{{ $navbarLocationName }}</span><span class="material-symbols-outlined text-[16px] text-gray-400">arrow_drop_down</span></summary>
                            <div class="absolute left-0 top-10 z-50 w-56 rounded-xl border border-[#EAEAEA] bg-white p-2 text-gray-800 shadow-xl">
                                <form method="POST" action="{{ route('location-context.update') }}">@csrf<input type="hidden" name="location_id" value=""><button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-50">{{ !$navbarSelectedLocationId ? 'Semua Lokasi ✓' : 'Semua Lokasi' }}</button></form>
                                @foreach($navbarLocations as $navbarLocation)<form method="POST" action="{{ route('location-context.update') }}">@csrf<input type="hidden" name="location_id" value="{{ $navbarLocation->id }}"><button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-50">{{ (string)$navbarSelectedLocationId === (string)$navbarLocation->id ? '✓ ' : '' }}{{ $navbarLocation->name }}</button></form>@endforeach
                            </div>
                        </details>
                        @else
                        <span class="flex items-center gap-1.5 px-2.5 py-1.5 bg-[#FBF9F8] border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-700"><span class="material-symbols-outlined text-[16px] text-gray-500">location_on</span>{{ $navbarLocationName }}</span>
                        @endif
                    </div>
                    <!-- Center: Search -->
                    <div class="flex justify-center">
                        <form method="GET" action="{{ route('search.index') }}" class="relative w-full max-w-md">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="search" id="d-search-input" name="q" value="{{ request()->routeIs('search.index') ? request('q') : '' }}" minlength="2" maxlength="100" autocomplete="off"
                                placeholder="Cari nama produk, SKU, batch, supplier..."
                                class="w-full pl-9 pr-10 h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all"
                            >
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[10px] text-gray-400 font-mono">⌘K</span>
                        </form>
                    </div>
                    <!-- Right: Notif + Avatar -->
                    <div class="flex items-center justify-end gap-1.5">
                        @if(auth()->user()->hasPermissionTo('low_stock.view'))
                            <details class="relative">
                                <summary aria-label="Notifikasi ({{ $unreadLowStockCount }} belum dibaca)" class="relative list-none cursor-pointer w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">notifications</span>
                                    @if($unreadLowStockCount > 0)<span class="absolute -top-1 -right-1 min-w-4 h-4 px-1 bg-[#DC143C] text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $unreadLowStockCount > 9 ? '9+' : $unreadLowStockCount }}</span>@endif
                                </summary>
                                <div class="absolute right-0 top-11 z-50 w-80 rounded-xl border border-[#EAEAEA] bg-white p-3 text-gray-800 shadow-xl">
                                    <div class="mb-2 flex items-center justify-between"><b class="text-sm">Notifikasi</b><span class="text-[11px] text-gray-500">{{ $unreadLowStockCount }} belum dibaca</span></div>
                                    @forelse($navbarNotifications as $notification)
                                        <a href="{{ route('low-stock.index') }}" class="block rounded-lg border-t border-gray-100 px-2 py-2.5 hover:bg-gray-50"><b class="block text-xs">{{ $notification->data['product'] ?? 'Peringatan stok' }}</b><span class="mt-0.5 block text-[11px] text-gray-500">{{ $notification->data['location'] ?? '' }} · {{ $notification->data['message'] ?? 'Stok mencapai batas minimum.' }}</span></a>
                                    @empty
                                        <p class="border-t border-gray-100 py-4 text-center text-xs text-gray-500">Tidak ada notifikasi baru.</p>
                                    @endforelse
                                    <a href="{{ route('low-stock.index') }}" class="mt-2 block rounded-lg bg-gray-50 px-3 py-2 text-center text-xs font-semibold text-[#DC143C]">Lihat semua alert stok</a>
                                </div>
                            </details>
                        @endif
                        <details data-close-on-outside class="relative">
                            <summary aria-label="Menu profil" class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 transition-colors">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 bg-gray-100 text-gray-600">
                                    <span class="material-symbols-outlined text-[18px]">person</span>
                                </span>
                                <span class="text-xs font-semibold text-gray-800 hidden xl:inline max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                                <span class="material-symbols-outlined text-[16px] text-gray-400">expand_more</span>
                            </summary>
                            <div class="absolute right-0 top-11 z-50 w-56 rounded-xl border border-[#EAEAEA] bg-white p-2 text-gray-800 shadow-xl">
                                <p class="truncate px-3 py-2 text-xs font-semibold text-gray-500">{{ auth()->user()->email }}</p>
                                <a href="{{ route('account') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-gray-50">Profil &amp; Pengaturan</a>
                                @if(auth()->user()->hasPermissionTo('users.manage'))
                                    <a href="{{ route('admin.roles.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-gray-50">Role &amp; Permission</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-[#DC143C] hover:bg-gray-50">Keluar</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            </header>

            <!-- Desktop Page Content -->
            <main class="pt-20 pb-8 px-4 sm:px-6 xl:px-8 space-y-6 min-w-0">
                @yield('desktop-content')
            </main>

        </div><!-- end right column -->
    </div><!-- end desktop wrapper -->

</div><!-- end root wrapper -->

<script>
    function openMobileDrawer() {
        const drawer = document.getElementById('m-drawer');
        const overlay = document.getElementById('m-drawer-overlay');
        if (!drawer || !overlay) return;
        overlay.classList.remove('hidden');
        drawer.classList.remove('-translate-x-full');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileDrawer() {
        const drawer = document.getElementById('m-drawer');
        const overlay = document.getElementById('m-drawer-overlay');
        if (!drawer || !overlay) return;
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeMobileDrawer(); });
    document.addEventListener('pointerdown', function(e) {
        document.querySelectorAll('details[data-close-on-outside][open]').forEach(function(menu) {
            if (!menu.contains(e.target)) menu.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('details[data-close-on-outside][open]').forEach(function(menu) {
                menu.removeAttribute('open');
            });
        }
    });
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            const input = document.getElementById('d-search-input');
            if (input) input.focus();
        }
    });

    @yield('page-scripts')
</script>
@endsection
