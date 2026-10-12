@extends('layouts.dashboard')

@php $pageTitle = $product->name . ' — Detail Stock · Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full space-y-0">

    <!-- Hero Banner -->
    <div class="relative bg-gradient-to-br from-[#DC143C] via-[#c01230] to-[#8b0a21] px-4 pt-5 pb-8 overflow-hidden">
        <!-- Decorative circles -->
        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-10 -left-4 w-40 h-40 rounded-full bg-white/5"></div>

        <a href="{{ route('inventory.stock.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-white/70 hover:text-white mb-4 relative z-10">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>Data Stock
        </a>

        <div class="flex items-start justify-between gap-3 relative z-10">
            <div class="min-w-0">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-white/50">{{ $product->category->name }}</span>
                <h1 class="font-poppins text-xl font-bold text-white mt-0.5 leading-tight">{{ $product->name }}</h1>
                <p class="font-mono text-white/60 text-xs mt-1">{{ $product->sku }}</p>
            </div>
            <span @class([
                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold shrink-0 mt-1',
                'bg-white/20 text-white' => $product->is_active,
                'bg-white/10 text-white/50' => !$product->is_active,
            ])>
                <span @class(['w-1.5 h-1.5 rounded-full', 'bg-emerald-400' => $product->is_active, 'bg-white/30' => !$product->is_active])></span>
                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>

        <!-- Stock number -->
        <div class="mt-5 relative z-10">
            <p class="text-white/60 text-[11px] uppercase tracking-widest font-semibold">Saldo Stok</p>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="font-poppins text-5xl font-bold text-white tabular-nums">{{ number_format($product->current_stock, 0, ',', '.') }}</span>
                <span class="text-white/70 text-lg font-semibold">{{ $product->unit->symbol }}</span>
            </div>
        </div>
    </div>

    <!-- Quick stat chips (overlap hero) -->
    <div class="px-4 -mt-4 relative z-10 flex gap-2.5 overflow-x-auto pb-1 scrollbar-none">
        <div class="bg-white rounded-2xl shadow-[0_4px_16px_rgba(0,0,0,0.12)] px-4 py-3 flex items-center gap-2.5 shrink-0 min-w-[140px]">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-emerald-600 text-[18px]">trending_up</span>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 font-semibold uppercase">Masuk</p>
                <p class="text-sm font-bold text-gray-900">{{ $movements->where('type','in')->sum('quantity') > 0 ? number_format($movements->where('type','in')->sum('quantity'), 0, ',', '.') : '—' }} <span class="text-xs font-normal text-gray-400">{{ $product->unit->symbol }}</span></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-[0_4px_16px_rgba(0,0,0,0.12)] px-4 py-3 flex items-center gap-2.5 shrink-0 min-w-[140px]">
            <div class="w-8 h-8 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#DC143C] text-[18px]">trending_down</span>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 font-semibold uppercase">Keluar</p>
                <p class="text-sm font-bold text-gray-900">{{ $movements->where('type','out')->sum('quantity') > 0 ? number_format($movements->where('type','out')->sum('quantity'), 0, ',', '.') : '—' }} <span class="text-xs font-normal text-gray-400">{{ $product->unit->symbol }}</span></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-[0_4px_16px_rgba(0,0,0,0.12)] px-4 py-3 flex items-center gap-2.5 shrink-0 min-w-[140px]">
            <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-blue-500 text-[18px]">receipt_long</span>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 font-semibold uppercase">Transaksi</p>
                <p class="text-sm font-bold text-gray-900">{{ $movements->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="flex flex-col p-4 space-y-4 mt-3">

        <!-- Product details -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100">
                <span class="material-symbols-outlined text-gray-400 text-[18px]">info</span>
                <h2 class="text-sm font-semibold text-gray-800">Detail Produk</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-xs text-gray-400">Kategori</span>
                    <span class="text-sm font-medium text-gray-900">{{ $product->category->name }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-xs text-gray-400">Satuan</span>
                    <span class="text-sm font-medium text-gray-900">{{ $product->unit->name }} <span class="text-gray-400 font-mono">({{ $product->unit->symbol }})</span></span>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-xs text-gray-400">Batch / Exp</span>
                    <span class="text-sm font-medium text-gray-900">{{ $batches->count() }} batch tercatat</span>
                </div>
                @if($product->description)
                <div class="px-4 py-3">
                    <span class="text-xs text-gray-400 block mb-1">Deskripsi</span>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3"><h2 class="text-sm font-semibold text-gray-800">Batch &amp; Kedaluwarsa</h2><p class="mt-1 text-[11px] text-gray-500">Saldo sisa per batch dan lokasi.</p></div>
            <div class="divide-y divide-gray-100">@forelse($batches as $batch)
                @php($expiry = $batch->expiry_date ? \Illuminate\Support\Carbon::parse($batch->expiry_date) : null)
                @php($ageDays = max(0, (int) \Illuminate\Support\Carbon::parse($batch->received_at)->startOfDay()->diffInDays(today())))
                <div class="space-y-1 px-4 py-3"><div class="flex justify-between gap-2"><b class="text-sm">{{ $batch->batch_number ?: 'Batch tanpa nomor' }}</b><b class="text-sm">{{ \App\Support\QuantityFormatter::format((float) $batch->remaining) }} {{ $product->unit->symbol }}</b></div><p class="text-[11px] text-gray-500">{{ $batch->location_name }} · diterima {{ \Illuminate\Support\Carbon::parse($batch->received_at)->format('d M Y') }}</p><p class="text-[11px] {{ $expiry && $expiry->isBefore(today()) ? 'font-semibold text-red-700' : ($expiry && $expiry->lessThanOrEqualTo(today()->addDays(30)) ? 'font-semibold text-amber-700' : 'text-gray-500') }}">{{ $expiry ? 'Kedaluwarsa '.$expiry->format('d M Y').($expiry->isBefore(today()) ? ' · Sudah kedaluwarsa' : ($expiry->lessThanOrEqualTo(today()->addDays(30)) ? ' · Segera kedaluwarsa' : '')) : 'Tanpa tanggal kedaluwarsa · umur stok '.$ageDays.' hari' }}</p></div>
            @empty<div class="p-4 text-xs text-gray-500">Belum ada batch yang dicatat.</div>@endforelse</div>
        </div>

        <!-- Movement timeline -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-400 text-[18px]">timeline</span>
                    <h2 class="text-sm font-semibold text-gray-800">Riwayat Pergerakan</h2>
                </div>
                <span class="text-[11px] font-semibold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $movements->count() }}</span>
            </div>
            @forelse($movements as $i => $movement)
            <div class="flex gap-3 px-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                <!-- Timeline dot -->
                <div class="flex flex-col items-center pt-0.5 shrink-0">
                    <div @class(['w-7 h-7 rounded-full flex items-center justify-center', 'bg-emerald-100' => $movement->type === 'in', 'bg-red-100' => $movement->type === 'out'])>
                        <span @class(['material-symbols-outlined text-[14px]', 'text-emerald-600' => $movement->type === 'in', 'text-[#DC143C]' => $movement->type === 'out'])>
                            {{ $movement->type === 'in' ? 'add' : 'remove' }}
                        </span>
                    </div>
                    @if(!$loop->last)<div class="w-px flex-1 bg-gray-100 mt-1.5"></div>@endif
                </div>
                <!-- Content -->
                <div class="flex-1 min-w-0 pb-1">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold {{ $movement->type === 'in' ? 'text-emerald-700' : 'text-[#DC143C]' }}">
                                {{ $movement->type === 'in' ? 'Stok Masuk' : 'Stok Keluar' }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $movement->location->name ?? 'Lokasi tidak tercatat' }}</p>
                        </div>
                        <span class="text-sm font-bold font-mono shrink-0 {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-[#DC143C]' }}">
                            {{ $movement->type === 'in' ? '+' : '-' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }}
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $movement->user->name ?? '—' }}</p>
                </div>
            </div>
            @empty
            <div class="py-10 text-center">
                <span class="material-symbols-outlined text-[40px] text-gray-200 block mb-2">receipt_long</span>
                <p class="text-sm text-gray-400">Belum ada transaksi stok.</p>
            </div>
            @endforelse
        </div>

        @if(auth()->user()->hasRole('super_admin'))
        <a href="{{ route('admin.master-data.edit', ['products', $product->id]) }}"
           class="w-full h-11 border border-gray-200 rounded-2xl flex items-center justify-center gap-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            <span class="material-symbols-outlined text-[18px]">edit</span>Edit Data Produk
        </a>
        @endif
    </div>
</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<div class="space-y-6 min-w-0">

    <!-- Hero Header -->
    <div class="relative bg-gradient-to-r from-[#DC143C] via-[#c01230] to-[#961029] rounded-2xl px-8 py-7 overflow-hidden shadow-[0_8px_32px_rgba(220,20,60,0.22)]">
        <!-- Decorative background shapes -->
        <div class="absolute top-0 right-0 w-72 h-72 rounded-full bg-white/5 -translate-y-1/3 translate-x-1/4"></div>
        <div class="absolute bottom-0 right-1/3 w-48 h-48 rounded-full bg-white/5 translate-y-1/2"></div>

        <div class="relative z-10 flex items-start justify-between gap-6">
            <div class="min-w-0">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 text-xs text-white/50 mb-3">
                    <a href="{{ route('inventory.stock.index') }}" class="hover:text-white/80 transition-colors font-medium">Data Stock</a>
                    <span class="material-symbols-outlined text-[13px]">chevron_right</span>
                    <span class="text-white/80 truncate">{{ $product->name }}</span>
                </nav>

                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="font-poppins text-3xl font-bold text-white tracking-tight">{{ $product->name }}</h1>
                    @if($product->is_active)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/15 text-white backdrop-blur-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 text-white/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-white/30"></span>Nonaktif
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 mt-2 text-white/60 text-sm">
                    <span class="font-mono bg-white/10 px-2 py-0.5 rounded text-xs">{{ $product->sku }}</span>
                    <span>{{ $product->category->name }}</span>
                    <span>·</span>
                    <span>{{ $product->unit->name }}</span>
                </div>
            </div>

            <!-- Stock balance -->
            <div class="shrink-0 text-right">
                <p class="text-[11px] uppercase tracking-widest text-white/50 font-semibold">Saldo Stok</p>
                <div class="flex items-baseline gap-2 mt-1 justify-end">
                    <span class="font-poppins text-5xl font-bold text-white tabular-nums">{{ number_format($product->current_stock, 0, ',', '.') }}</span>
                    <span class="text-white/70 text-xl font-semibold">{{ $product->unit->symbol }}</span>
                </div>
                @if(auth()->user()->hasRole('super_admin'))
                <a href="{{ route('admin.master-data.edit', ['products', $product->id]) }}"
                   class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-white/15 hover:bg-white/25 text-white text-xs font-semibold rounded-xl transition-colors backdrop-blur-sm">
                    <span class="material-symbols-outlined text-[15px]">edit</span>Edit Produk
                </a>
                @endif
            </div>
        </div>

        <!-- Stat chips row -->
        <div class="relative z-10 mt-6 flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5">
                <span class="material-symbols-outlined text-emerald-400 text-[18px]">trending_up</span>
                <div>
                    <p class="text-[10px] text-white/50 uppercase font-semibold">Total Masuk</p>
                    <p class="text-sm font-bold text-white">{{ $movements->where('type','in')->sum('quantity') > 0 ? number_format($movements->where('type','in')->sum('quantity'), 2, ',', '.') : '—' }} <span class="text-white/50 font-normal text-xs">{{ $product->unit->symbol }}</span></p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5">
                <span class="material-symbols-outlined text-orange-300 text-[18px]">trending_down</span>
                <div>
                    <p class="text-[10px] text-white/50 uppercase font-semibold">Total Keluar</p>
                    <p class="text-sm font-bold text-white">{{ $movements->where('type','out')->sum('quantity') > 0 ? number_format($movements->where('type','out')->sum('quantity'), 2, ',', '.') : '—' }} <span class="text-white/50 font-normal text-xs">{{ $product->unit->symbol }}</span></p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5">
                <span class="material-symbols-outlined text-blue-300 text-[18px]">receipt_long</span>
                <div>
                    <p class="text-[10px] text-white/50 uppercase font-semibold">Transaksi</p>
                    <p class="text-sm font-bold text-white">{{ $movements->count() }} catatan</p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5">
                <span class="material-symbols-outlined text-purple-300 text-[18px]">location_on</span>
                <div>
                    <p class="text-[10px] text-white/50 uppercase font-semibold">Batch / Exp</p>
                    <p class="text-sm font-bold text-white/40 italic">Belum tersedia</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Two-column body -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">

        <!-- Left: Product info (2/5) -->
        <div class="xl:col-span-2 space-y-4">

            <!-- Info card -->
            <div class="bg-white border border-[#EAEAEA] rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-[#EAEAEA]">
                    <h2 class="text-sm font-semibold text-gray-900">Informasi Produk</h2>
                </div>
                <dl class="divide-y divide-[#F5F5FA]">
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <dt class="text-xs text-gray-400 font-medium">Kategori</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $product->category->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <dt class="text-xs text-gray-400 font-medium">Satuan</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $product->unit->name }} <span class="text-gray-400 font-mono font-normal text-xs">({{ $product->unit->symbol }})</span></dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <dt class="text-xs text-gray-400 font-medium">SKU</dt>
                        <dd><span class="font-mono text-xs font-semibold text-gray-600 bg-gray-100 px-2.5 py-1 rounded-lg">{{ $product->sku }}</span></dd>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <dt class="text-xs text-gray-400 font-medium">Batch</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ $batches->count() }} tercatat</dd>
                    </div>
                    @if($product->description)
                    <div class="px-5 py-3.5">
                        <dt class="text-xs text-gray-400 font-medium mb-2">Deskripsi</dt>
                        <dd class="text-sm text-gray-600 leading-relaxed">{{ $product->description }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="overflow-hidden rounded-2xl border border-[#EAEAEA] bg-white shadow-sm">
                <div class="border-b border-[#EAEAEA] px-5 py-4"><h2 class="text-sm font-semibold text-gray-900">Batch &amp; Kedaluwarsa</h2><p class="mt-1 text-xs text-gray-500">Sisa dihitung dari mutasi batch.</p></div>
                <div class="divide-y divide-gray-100">@forelse($batches as $batch)
                    @php($expiry = $batch->expiry_date ? \Illuminate\Support\Carbon::parse($batch->expiry_date) : null)
                    @php($ageDays = max(0, (int) \Illuminate\Support\Carbon::parse($batch->received_at)->startOfDay()->diffInDays(today())))
                    <div class="space-y-1 px-5 py-3"><div class="flex items-start justify-between gap-3"><p class="text-sm font-semibold">{{ $batch->batch_number ?: 'Batch tanpa nomor' }}</p><b class="whitespace-nowrap text-sm">{{ \App\Support\QuantityFormatter::format((float) $batch->remaining) }} {{ $product->unit->symbol }}</b></div><p class="text-xs text-gray-500">{{ $batch->location_name }} · Diterima {{ \Illuminate\Support\Carbon::parse($batch->received_at)->format('d M Y') }}</p><p class="text-xs {{ $expiry && $expiry->isBefore(today()) ? 'font-semibold text-red-700' : ($expiry && $expiry->lessThanOrEqualTo(today()->addDays(30)) ? 'font-semibold text-amber-700' : 'text-gray-500') }}">{{ $expiry ? 'Kedaluwarsa '.$expiry->format('d M Y').($expiry->isBefore(today()) ? ' · Kedaluwarsa' : ($expiry->lessThanOrEqualTo(today()->addDays(30)) ? ' · Segera kedaluwarsa' : '')) : 'Tanpa tanggal kedaluwarsa · umur stok '.$ageDays.' hari' }}</p></div>
                @empty<div class="p-5 text-sm text-gray-500">Belum ada batch yang dicatat untuk produk ini.</div>@endforelse</div>
            </div>
        </div>

        <!-- Right: Movements timeline (3/5) -->
        <div class="xl:col-span-3 bg-white border border-[#EAEAEA] rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#EAEAEA] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Riwayat Pergerakan</h2>
                    <p class="text-xs text-gray-400 mt-0.5">20 transaksi terakhir</p>
                </div>
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">{{ $movements->count() }}</span>
            </div>

            @if($movements->isEmpty())
                <div class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <span class="material-symbols-outlined text-[32px] text-gray-300">receipt_long</span>
                    </div>
                    <p class="font-semibold text-gray-600">Belum ada transaksi</p>
                    <p class="mt-1 text-sm text-gray-400">Pergerakan stok akan tercatat di sini.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[440px]">
                        <thead>
                            <tr class="bg-[#FAFAFA] text-[11px] font-semibold text-gray-400 uppercase tracking-wider border-b border-[#F0F0F0]">
                                <th class="py-3 px-6">Tipe</th>
                                <th class="py-3 px-4">Lokasi</th>
                                <th class="py-3 px-4">Dicatat oleh</th>
                                <th class="py-3 px-4 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                            <tr class="border-b border-[#F5F5F5] hover:bg-gray-50/80 transition-colors last:border-0">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2.5">
                                        <div @class([
                                            'w-8 h-8 rounded-xl flex items-center justify-center shrink-0',
                                            'bg-emerald-100' => $movement->type === 'in',
                                            'bg-red-100'     => $movement->type === 'out',
                                        ])>
                                            <span @class([
                                                'material-symbols-outlined text-[16px]',
                                                'text-emerald-600' => $movement->type === 'in',
                                                'text-[#DC143C]'   => $movement->type === 'out',
                                            ])>{{ $movement->type === 'in' ? 'add' : 'remove' }}</span>
                                        </div>
                                        <div>
                                            <p @class([
                                                'text-sm font-semibold',
                                                'text-emerald-700' => $movement->type === 'in',
                                                'text-[#DC143C]'   => $movement->type === 'out',
                                            ])>{{ $movement->type === 'in' ? 'Masuk' : 'Keluar' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-sm text-gray-500">{{ $movement->location?->name ?? '—' }}</td>
                                <td class="py-4 px-4 text-sm text-gray-500">{{ $movement->user->name ?? '—' }}</td>
                                <td class="py-4 px-4 text-right">
                                    <span @class([
                                        'font-mono text-sm font-bold',
                                        'text-emerald-600' => $movement->type === 'in',
                                        'text-[#DC143C]'   => $movement->type === 'out',
                                    ])>{{ $movement->type === 'in' ? '+' : '−' }}{{ \App\Support\QuantityFormatter::format($movement->quantity) }}</span>
                                    <span class="text-xs text-gray-400 ml-1">{{ $product->unit->symbol }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
