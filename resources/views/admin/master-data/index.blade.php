@extends('layouts.dashboard')

@php
    $pageTitle = $config['label'] . ' — Master Data · Technolife';
    $tabs = [
        'products'   => ['label' => 'Produk',          'icon' => 'inventory_2'],
        'categories' => ['label' => 'Kategori',         'icon' => 'category'],
        'units'      => ['label' => 'Satuan',           'icon' => 'straighten'],
        'suppliers'  => ['label' => 'Supplier',         'icon' => 'local_shipping'],
        'locations'  => ['label' => 'Gudang & Outlet',  'icon' => 'location_on'],
    ];
    // Per-resource secondary info helper
    $infoText = fn($item) => match($resource) {
        'products'   => ($item->category->name ?? '—') . ' · ' . ($item->unit->symbol ?? '—'),
        'categories' => $item->description ?: '—',
        'units'      => 'Simbol: ' . ($item->symbol ?? '—'),
        'suppliers'  => $item->contact_person ?: ($item->email ?: ($item->phone ?: '—')),
        'locations'  => $item->type === 'warehouse' ? 'Gudang' : 'Outlet',
        default      => '—',
    };
    $identSub = fn($item) => match($resource) {
        'products'  => $item->sku ?? '',
        'units'     => $item->symbol ?? '',
        'locations' => $item->code ?? '',
        default     => '',
    };
@endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-poppins text-lg font-semibold text-on-surface">Master Data</h1>
            <p class="text-xs text-secondary mt-0.5">{{ $config['label'] }} · {{ $items->total() }} data</p>
        </div>
        <a href="{{ route('admin.master-data.create', $resource) }}"
           class="h-9 px-3.5 bg-primary text-on-primary text-xs font-semibold rounded-xl flex items-center gap-1.5 shadow-sm active:scale-95 transition-transform shrink-0">
            <span class="material-symbols-outlined text-[16px]">add</span>Tambah
        </a>
    </div>

    <!-- Resource Tabs (horizontal scroll) -->
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 scrollbar-none">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.master-data.index', $key) }}"
               class="whitespace-nowrap flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-colors shrink-0
                      {{ $resource === $key
                           ? 'bg-primary text-on-primary shadow-sm'
                           : 'bg-surface-container text-secondary hover:bg-surface-variant' }}">
                <span class="material-symbols-outlined text-[14px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <!-- Flash -->
    @if(session('status'))
    <div id="flash-mobile" class="rounded-xl border border-green-200 bg-green-50 p-3 flex items-center justify-between gap-2.5 text-sm text-green-800">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-green-500 text-[18px] shrink-0">check_circle</span>
            {{ session('status') }}
        </div>
        <button onclick="document.getElementById('flash-mobile').remove()" class="text-green-500 hover:text-green-700 shrink-0">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    @endif

    <!-- Search + Filter -->
    <form method="GET" class="flex flex-col gap-2">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </div>
            <input name="q" value="{{ $q }}"
                placeholder="Cari {{ strtolower($config['label']) }}..."
                class="w-full pl-9 pr-4 h-10 bg-surface-container-lowest border border-outline rounded-xl text-sm text-on-surface placeholder:text-secondary/60 focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none transition-all">
        </div>
        <div class="flex gap-2">
            <select name="status"
                class="flex-1 h-9 bg-surface-container-lowest border border-outline rounded-xl px-3 text-xs text-on-surface focus:border-primary outline-none">
                <option value="">Semua status</option>
                <option value="active"   @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            @if($resource === 'locations')
            <select name="type"
                class="flex-1 h-9 bg-surface-container-lowest border border-outline rounded-xl px-3 text-xs text-on-surface focus:border-primary outline-none">
                <option value="">Semua tipe</option>
                <option value="warehouse" @selected(request('type') === 'warehouse')>Gudang</option>
                <option value="outlet"    @selected(request('type') === 'outlet')>Outlet</option>
            </select>
            @endif
            <button type="submit"
                class="h-9 px-4 bg-primary text-on-primary text-xs font-semibold rounded-xl shrink-0">Cari</button>
        </div>
    </form>

    <!-- Item Cards -->
    @if($items->isEmpty())
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <span class="material-symbols-outlined text-[40px] text-secondary/40 block mb-2">{{ $tabs[$resource]['icon'] }}</span>
            <p class="font-semibold text-on-surface text-sm">Belum ada data {{ strtolower($config['label']) }}</p>
            <p class="text-xs text-secondary mt-1">Gunakan tombol tambah untuk membuat data pertama.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($items as $item)
            <div class="bg-surface-container-lowest rounded-2xl border border-outline shadow-xs overflow-hidden">
                <div class="flex items-center gap-3 p-3.5">
                    <!-- Icon -->
                    @if($resource === 'products')
                    <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : asset('images/product-placeholder.svg') }}" alt="" class="w-10 h-10 rounded-xl object-cover shrink-0">
                    @else
                    <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-secondary text-[20px]">{{ $tabs[$resource]['icon'] }}</span></div>
                    @endif
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.master-data.show', [$resource, $item->id]) }}"
                               class="text-sm font-semibold text-on-surface truncate hover:text-primary transition-colors">
                                {{ $item->name }}
                            </a>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0',
                                'bg-green-50 text-green-700' => $item->is_active,
                                'bg-gray-100 text-gray-500'  => !$item->is_active,
                            ])>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        @if($identSub($item))
                            <p class="font-mono text-secondary text-[11px] mt-0.5">{{ $identSub($item) }}</p>
                        @endif
                        <p class="text-xs text-secondary mt-0.5 truncate">{{ $infoText($item) }}</p>
                    </div>
                    <!-- Edit arrow -->
                    <a href="{{ route('admin.master-data.edit', [$resource, $item->id]) }}"
                       class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-secondary hover:text-primary transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($items->hasPages())
            <div class="pt-1">{{ $items->links() }}</div>
        @endif
    @endif

</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')

    <!-- Header Row -->
    <div class="flex items-center justify-between gap-4 min-w-0">
        <div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">Master Data</h1>
            <p class="text-xs text-gray-500 mt-0.5">Kelola data dasar inventory — produk, kategori, satuan, supplier, dan lokasi.</p>
        </div>
        <a href="{{ route('admin.master-data.create', $resource) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-[#c01230] transition-colors shrink-0">
            <span class="material-symbols-outlined text-[18px]">add</span>Tambah {{ $config['label'] }}
        </a>
    </div>

    <!-- Resource Tab Nav -->
    <nav class="flex items-center gap-1 p-1 bg-[#F5F5FA] rounded-xl w-fit">
        @foreach($tabs as $key => $tab)
            <a href="{{ route('admin.master-data.index', $key) }}"
               class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all
                      {{ $resource === $key
                           ? 'bg-white text-[#DC143C] shadow-sm'
                           : 'text-gray-500 hover:text-gray-800 hover:bg-white/60' }}">
                <span class="material-symbols-outlined text-[15px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <!-- Flash -->
    @if(session('status'))
    <div id="flash-desktop" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 flex items-center justify-between gap-3 text-sm text-green-800">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-green-500 text-[18px] shrink-0">check_circle</span>
            {{ session('status') }}
        </div>
        <button onclick="document.getElementById('flash-desktop').remove()" class="text-green-400 hover:text-green-600 transition-colors shrink-0">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    @endif

    <!-- Filter Bar + Table Card -->
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
                    <input name="q" value="{{ $q }}"
                        placeholder="Cari {{ strtolower($config['label']) }}..."
                        class="w-full pl-8 pr-4 h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all">
                </div>
                <!-- Status -->
                <select name="status"
                    class="h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] outline-none cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="active"   @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                @if($resource === 'locations')
                <select name="type"
                    class="h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg px-3 text-xs text-gray-700 focus:border-[#DC143C] outline-none cursor-pointer">
                    <option value="">Semua Tipe</option>
                    <option value="warehouse" @selected(request('type') === 'warehouse')>Gudang</option>
                    <option value="outlet"    @selected(request('type') === 'outlet')>Outlet</option>
                </select>
                @endif
                <button type="submit"
                    class="h-9 px-4 bg-[#DC143C] text-white text-xs font-semibold rounded-lg hover:bg-[#c01230] transition-colors shrink-0">
                    Terapkan
                </button>
                @if($q || request('status') || request('type'))
                    <a href="{{ route('admin.master-data.index', $resource) }}"
                       class="h-9 px-3 text-xs font-medium text-gray-500 hover:text-gray-700 flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">close</span>Reset
                    </a>
                @endif
                <span class="ml-auto text-xs text-gray-400 font-medium">{{ $items->total() }} data</span>
            </form>
        </div>

        <!-- Table / Empty State -->
        @if($items->isEmpty())
            <div class="py-16 text-center">
                <span class="material-symbols-outlined text-[48px] text-gray-200 block mb-3">{{ $tabs[$resource]['icon'] }}</span>
                <p class="font-semibold text-gray-700">Belum ada data {{ strtolower($config['label']) }}</p>
                <p class="mt-1 text-sm text-gray-400">Gunakan tombol "Tambah" untuk membuat data pertama.</p>
                <a href="{{ route('admin.master-data.create', $resource) }}"
                   class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-[#DC143C] text-white text-sm font-semibold rounded-lg hover:bg-[#c01230] transition-colors">
                    <span class="material-symbols-outlined text-[16px]">add</span>Tambah {{ $config['label'] }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse min-w-[560px]">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Nama / Identitas</th>
                            <th class="py-3 px-4">Informasi</th>
                            <th class="py-3 px-4 w-28 text-center">Status</th>
                            <th class="py-3 px-4 w-28 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA] text-sm">
                        @foreach($items as $item)
                        <tr class="hover:bg-gray-50/70 transition-colors group">
                            <!-- Name + sub-identifier -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    @if($resource === 'products')
                                    <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : asset('images/product-placeholder.svg') }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0">
                                    @else
                                    <div class="w-8 h-8 rounded-lg bg-[#F5F5FA] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-gray-400 text-[16px]">{{ $tabs[$resource]['icon'] }}</span>
                                    </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.master-data.show', [$resource, $item->id]) }}"
                                           class="font-semibold text-gray-900 hover:text-[#DC143C] transition-colors">
                                            {{ $item->name }}
                                        </a>
                                        @if($identSub($item))
                                            <div class="mt-0.5">
                                                <span class="font-mono text-[11px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $identSub($item) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <!-- Secondary info -->
                            <td class="py-3.5 px-4 text-gray-500 text-xs">
                                {{ $infoText($item) }}
                            </td>
                            <!-- Status badge -->
                            <td class="py-3.5 px-4 text-center">
                                @if($item->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Nonaktif
                                    </span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.master-data.show', [$resource, $item->id]) }}"
                                       class="w-7 h-7 rounded-lg border border-[#EAEAEA] flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors"
                                       title="Lihat detail">
                                        <span class="material-symbols-outlined text-[15px]">open_in_new</span>
                                    </a>
                                    <a href="{{ route('admin.master-data.edit', [$resource, $item->id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#FFF1F0] text-[#DC143C] text-[11px] font-semibold hover:bg-[#FFE4E1] transition-colors"
                                       title="Edit">
                                        <span class="material-symbols-outlined text-[13px]">edit</span>Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-[#EAEAEA] flex items-center justify-between gap-4 text-xs text-gray-500">
                <div>
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $items->firstItem() }}–{{ $items->lastItem() }}</span>
                    dari <span class="font-semibold text-gray-700">{{ $items->total() }}</span> data
                </div>
                {{ $items->links() }}
            </div>
        @endif

    </section>

@endsection

@section('page-scripts')
    // Live search filter for desktop table
    (function() {
        const input = document.getElementById('d-search-input');
        if (!input) return;
        input.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = (q === '' || row.innerText.toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    })();
@endsection
