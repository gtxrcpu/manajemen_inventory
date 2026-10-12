@extends('layouts.dashboard')
@php($pageTitle = 'Stock Adjustment — Technolife')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-xl font-semibold text-gray-900">Stock Adjustment</h1>
            <p class="text-xs text-gray-600">Koreksi saldo dengan alasan dan audit</p>
        </div>
        <a href="{{ route('inventory.stock-adjustments.create') }}" class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">add</span>
            <span>Koreksi</span>
        </a>
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white border border-gray-200 rounded-xl p-3">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-700 text-[18px]">trending_up</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Penambahan</p>
            <p class="text-lg font-semibold text-gray-900 font-poppins mt-0.5">{{ $adjustments->where('direction', 'increase')->count() }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-3">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-700 text-[18px]">trending_down</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Pengurangan</p>
            <p class="text-lg font-semibold text-gray-900 font-poppins mt-0.5">{{ $adjustments->where('direction', 'decrease')->count() }}</p>
        </div>
    </div>

    <!-- List -->
    <div class="space-y-3">
        @forelse($adjustments as $adjustment)
        <a href="{{ route('inventory.stock-adjustments.show',$adjustment) }}" class="block rounded-xl border border-gray-200 bg-white p-4 active:scale-[0.99] transition-transform">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-sm text-gray-900">{{ $adjustment->reference }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold uppercase tracking-wide {{ $adjustment->direction === 'increase' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $adjustment->direction === 'increase' ? 'Tambah' : 'Kurang' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-900 truncate">{{ $adjustment->product->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $adjustment->location->name }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-base font-semibold font-poppins {{ $adjustment->direction === 'increase' ? 'text-green-700' : 'text-red-700' }}">
                        {{ $adjustment->direction === 'increase' ? '+' : '−' }}{{ number_format($adjustment->quantity,3,',','.') }}
                    </p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ $adjustment->product->unit->symbol }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <div class="flex items-center gap-1.5 text-xs text-gray-600">
                    <span class="material-symbols-outlined text-[14px]">person</span>
                    <span>{{ $adjustment->creator->name }}</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span>{{ $adjustment->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>
            @if($adjustment->reason)
            <div class="mt-3 p-2.5 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-600 leading-relaxed">{{ $adjustment->reason }}</p>
            </div>
            @endif
        </a>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-gray-400 text-[24px]">tune</span>
            </div>
            <p class="text-sm text-gray-500">Belum ada penyesuaian saldo.</p>
        </div>
        @endforelse
    </div>

    {{ $adjustments->links() }}
</main>
@endsection
@section('desktop-content')
<section class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900">Stock Adjustment</h1>
            <p class="mt-1 text-sm text-gray-500">Koreksi saldo yang tercatat sebagai transaksi permanen.</p>
        </div>
        <a href="{{ route('inventory.stock-adjustments.create') }}" class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 transition-colors flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Buat Adjustment</span>
        </a>
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[20px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-700 text-[22px]">tune</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Total Adjustment</p>
            <p class="text-2xl font-semibold text-gray-900 font-poppins mt-1">{{ $adjustments->total() }}</p>
            <p class="text-xs text-gray-500 mt-1">Semua transaksi</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-700 text-[22px]">trending_up</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Penambahan</p>
            <p class="text-2xl font-semibold text-green-700 font-poppins mt-1">{{ $adjustments->where('direction', 'increase')->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Koreksi naik</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-700 text-[22px]">trending_down</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Pengurangan</p>
            <p class="text-2xl font-semibold text-red-700 font-poppins mt-1">{{ $adjustments->where('direction', 'decrease')->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Koreksi turun</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-700 text-[22px]">calendar_month</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Bulan Ini</p>
            <p class="text-2xl font-semibold text-gray-900 font-poppins mt-1">{{ $adjustments->where('created_at', '>=', now()->startOfMonth())->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ now()->format('M Y') }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[900px]">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="p-4 font-semibold">Referensi & Tanggal</th>
                        <th class="p-4 font-semibold">Produk</th>
                        <th class="p-4 font-semibold">Lokasi</th>
                        <th class="p-4 font-semibold text-right">Koreksi</th>
                        <th class="p-4 font-semibold">Saldo Sebelum → Sesudah</th>
                        <th class="p-4 font-semibold">Alasan</th>
                        <th class="p-4 font-semibold">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($adjustments as $adjustment)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <a class="font-semibold text-gray-900 hover:text-gray-700" href="{{ route('inventory.stock-adjustments.show',$adjustment) }}">
                                {{ $adjustment->reference }}
                            </a>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 mt-1">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                <span>{{ $adjustment->created_at->format('d M Y H:i') }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <p class="font-medium text-gray-900">{{ $adjustment->product->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $adjustment->product->sku }}</p>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-gray-400 text-[16px]">location_on</span>
                                <span class="text-gray-700">{{ $adjustment->location->name }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg {{ $adjustment->direction === 'increase' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                <span class="material-symbols-outlined text-[16px]">{{ $adjustment->direction === 'increase' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                <span class="font-semibold">{{ $adjustment->direction === 'increase' ? '+' : '−' }}{{ number_format($adjustment->quantity,3,',','.') }}</span>
                                <span class="text-xs">{{ $adjustment->product->unit->symbol }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">{{ number_format($adjustment->old_quantity,3,',','.') }}</span>
                                <span class="material-symbols-outlined text-gray-400 text-[16px]">arrow_forward</span>
                                <span class="font-semibold text-gray-900">{{ number_format($adjustment->new_quantity,3,',','.') }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <p class="text-gray-700 max-w-xs truncate">{{ $adjustment->reason }}</p>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-gray-600 text-[16px]">person</span>
                                </div>
                                <span class="text-gray-700 text-sm">{{ $adjustment->creator->name }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-gray-400 text-[32px]">tune</span>
                            </div>
                            <p class="text-gray-500">Belum ada penyesuaian saldo.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $adjustments->links() }}
</section>
@endsection
