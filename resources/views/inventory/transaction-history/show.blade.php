@extends('layouts.dashboard')

@php $pageTitle = 'Detail Transaksi '.$reference.' — Technolife'; @endphp
@php
    $typeLabels = [
        'stock_in' => 'Stock In',
        'stock_out' => 'Stock Out',
        'stock_opname' => 'Stock Opname',
        'adjustment' => 'Adjustment',
        'audit' => 'Aktivitas / Audit',
    ];
    $typeConfig = match ($type) {
        'stock_in' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'move_to_inbox', 'label' => 'Stock In'],
        'stock_out' => ['badge' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'outbox', 'label' => 'Stock Out'],
        'stock_opname' => ['badge' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fact_check', 'label' => 'Stock Opname'],
        'adjustment' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'tune', 'label' => 'Adjustment'],
        'audit' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'security', 'label' => 'Audit'],
        default => ['badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'receipt', 'label' => ucfirst($type)],
    };
@endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full p-4 space-y-4">
    <!-- Back Button -->
    <a href="{{ route('inventory.transactions.index') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Kembali ke Riwayat</span>
    </a>

    <!-- Main Detail Card -->
    <section class="rounded-2xl border border-outline bg-white p-4 shadow-xs space-y-4">
        <!-- Top Row: Type & Status -->
        <div class="flex items-start justify-between gap-2 border-b border-outline/50 pb-3">
            <div>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $typeConfig['badge'] }}">
                    <span class="material-symbols-outlined text-[13px]">{{ $typeConfig['icon'] }}</span>
                    <span>{{ $typeConfig['label'] }}</span>
                </span>
                <h1 class="font-mono text-lg font-bold text-on-surface mt-1.5">{{ $reference }}</h1>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                {{ str_replace('_', ' ', ucfirst($status)) }}
            </span>
        </div>

        @if($description)
            <div class="bg-surface-container-low rounded-xl p-3 text-xs text-on-surface">
                <span class="text-[10px] font-semibold uppercase text-secondary block mb-0.5">Keterangan / Aktivitas</span>
                <p>{{ $description }}</p>
            </div>
        @endif

        <!-- Meta Grid -->
        <dl class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Waktu Catatan</dt>
                <dd class="mt-0.5 font-semibold text-on-surface">
                    {{ $timestamp ? \Illuminate\Support\Carbon::parse($timestamp)->format('d M Y, H:i') : '—' }}
                </dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Lokasi Gudang</dt>
                <dd class="mt-0.5 font-semibold text-on-surface truncate">
                    {{ $location?->name ?? 'Sistem / Global' }}
                </dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Petugas</dt>
                <dd class="mt-0.5 font-semibold text-on-surface truncate">
                    {{ $actor?->name ?? 'System' }}
                </dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Status</dt>
                <dd class="mt-0.5 font-semibold text-on-surface capitalize">
                    {{ str_replace('_', ' ', $status) }}
                </dd>
            </div>
        </dl>

        <!-- Items Section -->
        @if($items->isNotEmpty())
            <div class="pt-2">
                <h2 class="text-xs font-semibold text-on-surface mb-2">Produk & Jumlah Terkait ({{ $items->count() }})</h2>
                <div class="space-y-2">
                    @foreach($items as $item)
                        <div class="rounded-xl border border-outline/70 p-3 bg-surface-container-lowest text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-on-surface">{{ $item->product }}</p>
                                    <p class="font-mono text-[11px] text-secondary mt-0.5">{{ $item->sku }}</p>
                                </div>
                                <span class="font-bold text-on-surface text-sm">
                                    {{ number_format((float) $item->quantity, 3, ',', '.') }}
                                </span>
                            </div>
                            @if($item->physical_quantity !== null)
                                <div class="mt-2 pt-2 border-t border-outline/40 flex justify-between text-[11px] text-secondary">
                                    <span>Fisik: <b>{{ number_format((float) $item->physical_quantity, 3, ',', '.') }}</b></span>
                                    <span>Selisih: <b>{{ number_format((float) $item->physical_quantity - (float) $item->quantity, 3, ',', '.') }}</b></span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Audit Changes -->
        @if($changes)
            <div class="pt-2">
                <h2 class="text-xs font-semibold text-on-surface mb-2">Log Perubahan (Audit Trail)</h2>
                <pre class="bg-surface-container-low rounded-xl p-3 text-[11px] font-mono text-secondary overflow-x-auto whitespace-pre-wrap">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </section>
</main>
@endsection


{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT (>= lg)                                                 --}}
{{-- ======================================================================= --}}
@section('desktop-content')
<section class="max-w-4xl mx-auto space-y-5">
    <!-- Breadcrumb / Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.transactions.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#DC143C] hover:underline">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Kembali ke Riwayat Transaksi</span>
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[15px]">print</span>
                <span>Cetak Bukti</span>
            </button>
        </div>
    </div>

    <!-- Document Card -->
    <div class="rounded-2xl border border-[#EAEAEA] bg-white shadow-[0_4px_16px_rgba(0,0,0,0.04)] overflow-hidden">
        <!-- Document Header Banner -->
        <div class="p-6 border-b border-[#EAEAEA] bg-[#FDFCFB] flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $typeConfig['badge'] }}">
                        <span class="material-symbols-outlined text-[14px]">{{ $typeConfig['icon'] }}</span>
                        <span>{{ $typeConfig['label'] }}</span>
                    </span>
                </div>
                <h1 class="font-mono text-2xl font-bold text-gray-900">{{ $reference }}</h1>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $description ?: 'Transaksi tercatat di dalam sistem inventory.' }}
                </p>
            </div>

            <div class="text-right shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                    {{ str_replace('_', ' ', ucfirst($status)) }}
                </span>
            </div>
        </div>

        <!-- Meta Attributes Grid -->
        <div class="p-6 border-b border-[#EAEAEA]">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Waktu Transaksi</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm">
                        {{ $timestamp ? \Illuminate\Support\Carbon::parse($timestamp)->format('d M Y, H:i') : '—' }}
                    </dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Lokasi Gudang</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm truncate">
                        {{ $location?->name ?? 'Sistem / Global' }}
                    </dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Petugas / Pelaksana</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm truncate">
                        {{ $actor?->name ?? 'System' }}
                    </dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Status Catatan</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm capitalize">
                        {{ str_replace('_', ' ', $status) }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Items Table -->
        @if($items->isNotEmpty())
            <div class="p-6 border-b border-[#EAEAEA]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-poppins font-semibold text-sm text-gray-900">Rincian Produk Terkait</h2>
                    <span class="text-xs font-semibold text-gray-500">
                        {{ $items->count() }} Jenis Produk
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-[#EAEAEA]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-[#FBF9F8] border-b border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Nama Produk</th>
                                <th class="py-3 px-4 text-right">Kuantiti</th>
                                @if($items->whereNotNull('physical_quantity')->isNotEmpty())
                                    <th class="py-3 px-4 text-right">Kuantiti Fisik</th>
                                    <th class="py-3 px-4 text-right">Selisih</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EAEAEA]">
                            @foreach($items as $item)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="py-3 px-4 font-mono font-medium text-gray-700">{{ $item->sku }}</td>
                                    <td class="py-3 px-4 font-semibold text-gray-900">{{ $item->product }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-gray-900 text-sm">
                                        {{ number_format((float) $item->quantity, 3, ',', '.') }}
                                    </td>
                                    @if($items->whereNotNull('physical_quantity')->isNotEmpty())
                                        <td class="py-3 px-4 text-right font-medium text-gray-700">
                                            {{ $item->physical_quantity === null ? '—' : number_format((float) $item->physical_quantity, 3, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-semibold {{ ((float) $item->physical_quantity - (float) $item->quantity) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ $item->physical_quantity === null ? '—' : number_format((float) $item->physical_quantity - (float) $item->quantity, 3, ',', '.') }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Audit Trail / Changes JSON -->
        @if($changes)
            <div class="p-6 bg-gray-50/50">
                <h2 class="font-poppins font-semibold text-sm text-gray-900 mb-2">Audit Trail / Data Perubahan</h2>
                <div class="rounded-xl border border-gray-200 bg-white p-4 overflow-x-auto">
                    <pre class="text-xs font-mono text-gray-700 whitespace-pre-wrap">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
