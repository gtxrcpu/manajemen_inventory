@extends('layouts.dashboard')

@php $pageTitle = 'Detail Pengeluaran '.$issue->reference.' — Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT (< lg)                                                   --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<main class="w-full p-4 space-y-4">
    <!-- Back Button -->
    <a href="{{ route('inventory.stock-out.index') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Kembali ke Stock Out</span>
    </a>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 flex items-center gap-2 text-xs text-emerald-800">
            <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    <!-- Main Card -->
    <section class="rounded-2xl border border-outline bg-white p-4 shadow-xs space-y-4">
        <!-- Top Title & Badge -->
        <div class="flex items-start justify-between gap-2 border-b border-outline/50 pb-3">
            <div>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Dokumen Pengeluaran</span>
                <h1 class="font-mono text-lg font-bold text-on-surface mt-0.5">{{ $issue->reference }}</h1>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                Tercatat
            </span>
        </div>

        <!-- Meta Grid -->
        <dl class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Tanggal Keluar</dt>
                <dd class="mt-0.5 font-semibold text-on-surface">{{ $issue->issued_at->format('d M Y') }}</dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Lokasi Asal</dt>
                <dd class="mt-0.5 font-semibold text-on-surface truncate">{{ $issue->location->name }}</dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Tujuan</dt>
                <dd class="mt-0.5 font-semibold text-on-surface truncate text-rose-700">
                    {{ $issue->destination }}
                </dd>
            </div>
            <div class="bg-surface-container-low rounded-xl p-2.5">
                <dt class="text-[10px] text-secondary font-medium uppercase">Petugas</dt>
                <dd class="mt-0.5 font-semibold text-on-surface truncate">{{ $issue->issuer->name }}</dd>
            </div>
        </dl>

        @if($issue->notes)
            <div class="rounded-xl border border-outline/60 bg-surface-container-lowest p-3 text-xs text-secondary">
                <span class="text-[10px] font-semibold uppercase text-secondary/70 block mb-0.5">Catatan:</span>
                <p>{{ $issue->notes }}</p>
            </div>
        @endif

        <!-- Items Header -->
        <div class="pt-2">
            <div class="flex items-center justify-between text-xs font-semibold text-on-surface mb-2">
                <span>Rincian Barang Keluar ({{ $issue->items->count() }})</span>
                <span class="text-rose-600 font-bold">Total: {{ \App\Support\QuantityFormatter::format($issue->items->sum('quantity')) }}</span>
            </div>

            <div class="space-y-2">
                @foreach($issue->items as $item)
                    <div class="rounded-xl border border-outline/70 p-3 bg-surface-container-lowest text-xs">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-on-surface">{{ $item->product->name }}</p>
                                <p class="font-mono text-[11px] text-secondary mt-0.5">{{ $item->product->sku }}</p>
                            </div>
                            <span class="font-bold text-rose-700 text-sm whitespace-nowrap">
                                {{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}
                            </span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-outline/40 flex flex-wrap items-center gap-2 text-[11px] text-secondary">
                            <span>Alokasi Batch: <b>{{ $item->batch?->batch_number ?: 'Untracked' }}</b></span>
                            @if($item->batch?->expiry_date)
                                <span>•</span>
                                <span>Exp: <b>{{ $item->batch->expiry_date->format('d M Y') }}</b></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
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
        <a href="{{ route('inventory.stock-out.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#DC143C] hover:underline">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Kembali ke Riwayat Stock Out</span>
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-[#EAEAEA] bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-[15px]">print</span>
                <span>Cetak Bukti</span>
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-2.5 text-sm text-emerald-900 shadow-2xs">
            <span class="material-symbols-outlined text-emerald-600 text-[20px]">check_circle</span>
            <span class="font-medium">{{ session('status') }}</span>
        </div>
    @endif

    <!-- Document Card -->
    <div class="rounded-2xl border border-[#EAEAEA] bg-white shadow-[0_4px_16px_rgba(0,0,0,0.04)] overflow-hidden">
        <!-- Document Header Banner -->
        <div class="p-6 border-b border-[#EAEAEA] bg-[#FDFCFB] flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-rose-700 uppercase tracking-wider mb-1">
                    <span class="material-symbols-outlined text-[16px]">outbox</span>
                    <span>Surat Pengeluaran Barang (Stock Out)</span>
                </div>
                <h1 class="font-mono text-2xl font-bold text-gray-900">{{ $issue->reference }}</h1>
                <p class="text-xs text-gray-500 mt-1">Dicatat pada {{ $issue->created_at->format('d M Y, H:i') }} WIB</p>
            </div>

            <div class="text-right shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                    Tercatat di Ledger
                </span>
            </div>
        </div>

        <!-- Meta Attributes Grid -->
        <div class="p-6 border-b border-[#EAEAEA]">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Tanggal Keluar</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm">{{ $issue->issued_at->format('d M Y') }}</dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Lokasi Gudang Asal</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm truncate">{{ $issue->location->name }}</dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Tujuan Pengeluaran</dt>
                    <dd class="mt-1 font-semibold text-rose-700 text-sm truncate">
                        {{ $issue->destination }}
                    </dd>
                </div>
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl p-3">
                    <dt class="text-[10px] font-semibold uppercase text-gray-400">Petugas Pengeluar</dt>
                    <dd class="mt-1 font-semibold text-gray-900 text-sm truncate">{{ $issue->issuer->name }}</dd>
                </div>
            </dl>

            @if($issue->notes)
                <div class="mt-4 rounded-xl border border-gray-200/80 bg-gray-50/50 p-3.5 text-xs text-gray-600">
                    <span class="font-semibold text-gray-700 block mb-0.5">Catatan Tambahan:</span>
                    <p>{{ $issue->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Items Table -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-poppins font-semibold text-sm text-gray-900">Daftar Produk Dikeluarkan</h2>
                <span class="text-xs font-semibold text-gray-500">
                    Total: {{ \App\Support\QuantityFormatter::format($issue->items->sum('quantity')) }} Unit Barang
                </span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-[#EAEAEA]">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-b border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">SKU</th>
                            <th class="py-3 px-4">Nama Produk</th>
                            <th class="py-3 px-4">Alokasi Batch (FEFO)</th>
                            <th class="py-3 px-4">Kedaluwarsa</th>
                            <th class="py-3 px-4 text-right">Kuantiti</th>
                            <th class="py-3 px-4">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA]">
                        @foreach($issue->items as $item)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-medium text-gray-700">{{ $item->product->sku }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $item->product->name }}</td>
                                <td class="py-3 px-4">
                                    @if($item->batch?->batch_number)
                                        <span class="font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded text-[11px]">
                                            #{{ $item->batch->batch_number }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Untracked</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-700">
                                    {{ $item->batch?->expiry_date ? $item->batch->expiry_date->format('d M Y') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-rose-700 text-sm">
                                    {{ \App\Support\QuantityFormatter::format($item->quantity) }}
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ $item->product->unit->symbol }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
