@extends('layouts.dashboard')
@php($pageTitle = 'Alert Stok Minimum')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <!-- Header -->
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="font-poppins text-xl font-semibold text-gray-900">Stok Minimum</h1>
            <p class="mt-1 text-xs text-gray-600">Alert aktif dan riwayat stok di bawah batas minimum.</p>
        </div>
        @if($canManage)
        <a href="{{ route('low-stock.thresholds') }}" class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white flex items-center gap-1 shrink-0">
            <span class="material-symbols-outlined text-[16px]">settings</span>
            <span>Atur</span>
        </a>
        @endif
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white border border-gray-200 rounded-xl p-3">
            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined text-red-700 text-[18px]">error</span>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Alert Aktif</p>
            <p class="text-lg font-semibold text-red-700 font-poppins mt-0.5">{{ $activeAlerts->total() }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mb-2">
                <span class="material-symbols-outlined text-gray-700 text-[18px]">history</span>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Total Riwayat</p>
            <p class="text-lg font-semibold text-gray-900 font-poppins mt-0.5">{{ $history->total() }}</p>
        </div>
    </div>

    <!-- Active Alerts Section -->
    <section class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-red-600 text-[20px]">priority_high</span>
            <h2 class="font-semibold text-gray-900">Perlu restock</h2>
        </div>

        @forelse($activeAlerts as $alert)
        <article class="rounded-xl border-2 border-red-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $alert->product->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $alert->product->sku }}</p>
                    <div class="flex items-center gap-1.5 text-xs text-gray-600 mt-1">
                        <span class="material-symbols-outlined text-[14px]">location_on</span>
                        <span>{{ $alert->location->name }}</span>
                    </div>
                </div>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-semibold text-red-700 uppercase tracking-wide shrink-0">
                    Stok Rendah
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 p-3 bg-red-50 rounded-lg border border-red-100">
                <div>
                    <p class="text-[10px] text-red-600 uppercase font-medium mb-1">Saldo Saat Ini</p>
                    <p class="text-base font-semibold text-red-700 font-poppins">{{ \App\Support\QuantityFormatter::format($alert->stock_quantity) }}</p>
                    <p class="text-[10px] text-gray-600 uppercase">{{ $alert->product->unit->symbol }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500 uppercase font-medium mb-1">Batas Minimum</p>
                    <p class="text-base font-semibold text-gray-900 font-poppins">{{ \App\Support\QuantityFormatter::format($alert->minimum_quantity) }}</p>
                    <p class="text-[10px] text-gray-600 uppercase">{{ $alert->product->unit->symbol }}</p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 text-[11px] text-gray-500 mt-3">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                <span>Terjadi {{ $alert->triggered_at->format('d M Y H:i') }}</span>
            </div>
        </article>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-green-600 text-[24px]">check_circle</span>
            </div>
            <p class="text-sm font-medium text-gray-900 mb-1">Semua stok aman</p>
            <p class="text-xs text-gray-500">Tidak ada produk di bawah batas minimum</p>
        </div>
        @endforelse

        {{ $activeAlerts->links() }}
    </section>

    <!-- History Section -->
    <section class="space-y-3">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-gray-600 text-[20px]">history</span>
            <h2 class="font-semibold text-gray-900">Riwayat alert</h2>
        </div>
        <p class="text-xs text-gray-500 -mt-2">Alert tetap tersimpan setelah stok kembali aman.</p>

        @forelse($history as $alert)
        <article class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $alert->product->name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $alert->product->sku }} · {{ $alert->location->name }}
                    </p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide shrink-0 {{ $alert->resolved_at ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $alert->resolved_at ? 'Aman' : 'Aktif' }}
                </span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                <div class="text-xs text-gray-600">
                    <span class="font-medium text-gray-900">{{ \App\Support\QuantityFormatter::format($alert->stock_quantity) }}</span>
                    <span class="text-gray-500"> / </span>
                    <span class="font-medium text-gray-900">{{ \App\Support\QuantityFormatter::format($alert->minimum_quantity) }}</span>
                    <span class="text-gray-500 uppercase text-[10px]">{{ $alert->product->unit->symbol }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-gray-500 mt-3">
                <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span>{{ $alert->triggered_at->format('d M Y') }}</span>
                </div>
                @if($alert->resolved_at)
                <div class="flex items-center gap-1 text-green-600">
                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                    <span>{{ $alert->resolved_at->format('d M Y') }}</span>
                </div>
                @endif
            </div>
        </article>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-gray-400 text-[24px]">history</span>
            </div>
            <p class="text-sm text-gray-500">Riwayat alert masih kosong.</p>
        </div>
        @endforelse

        {{ $history->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900">Stok Minimum</h1>
            <p class="mt-1 text-sm text-gray-500">Pantau alert aktif dan riwayat stok di bawah batas.</p>
        </div>
        @if($canManage)
        <a href="{{ route('low-stock.thresholds') }}" class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 transition-colors flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">settings</span>
            <span>Atur batas minimum</span>
        </a>
        @endif
    </div>

    @if(session('status'))
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[20px] text-green-600 shrink-0">check_circle</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-red-700 text-[22px]">error</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Alert Aktif</p>
            <p class="text-2xl font-semibold text-red-700 font-poppins mt-1">{{ $activeAlerts->total() }}</p>
            <p class="text-xs text-gray-500 mt-1">Perlu restock segera</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-700 text-[22px]">check_circle</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Alert Teratasi</p>
            <p class="text-2xl font-semibold text-gray-900 font-poppins mt-1">{{ $history->where('resolved_at', '!=', null)->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Stok sudah kembali aman</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-700 text-[22px]">history</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 uppercase font-medium tracking-wider">Total Riwayat</p>
            <p class="text-2xl font-semibold text-gray-900 font-poppins mt-1">{{ $history->total() }}</p>
            <p class="text-xs text-gray-500 mt-1">Semua catatan alert</p>
        </div>
    </div>

    <!-- Active Alerts Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-5 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-600 text-[22px]">priority_high</span>
                    <h2 class="font-semibold text-gray-900">Perlu restock</h2>
                </div>
                <p class="mt-1 text-xs text-gray-500 flex items-center gap-1.5">
                    <span>{{ number_format($activeAlerts->total(), 0, ',', '.') }} alert aktif membutuhkan perhatian</span>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="p-4 font-semibold">Produk</th>
                        <th class="p-4 font-semibold">Lokasi</th>
                        <th class="p-4 font-semibold text-right">Saldo Saat Ini</th>
                        <th class="p-4 font-semibold text-right">Batas Minimum</th>
                        <th class="p-4 font-semibold">Mulai Alert</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($activeAlerts as $alert)
                    <tr class="hover:bg-red-50/30 transition-colors">
                        <td class="p-4">
                            <p class="font-semibold text-gray-900">{{ $alert->product->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $alert->product->sku }}</p>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-gray-400 text-[16px]">location_on</span>
                                <span class="text-gray-700">{{ $alert->location->name }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-right">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50">
                                <span class="material-symbols-outlined text-red-600 text-[16px]">warning</span>
                                <span class="font-semibold text-red-700">{{ \App\Support\QuantityFormatter::format($alert->stock_quantity) }}</span>
                                <span class="text-xs text-red-600 uppercase">{{ $alert->product->unit->symbol }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-right">
                            <span class="font-medium text-gray-900">{{ \App\Support\QuantityFormatter::format($alert->minimum_quantity) }}</span>
                            <span class="text-xs text-gray-500 uppercase ml-1">{{ $alert->product->unit->symbol }}</span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                <span>{{ $alert->triggered_at->format('d M Y H:i') }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-green-600 text-[32px]">check_circle</span>
                            </div>
                            <p class="font-medium text-gray-900 mb-1">Semua stok aman</p>
                            <p class="text-sm text-gray-500">Tidak ada produk di bawah batas minimum</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $activeAlerts->links() }}
        </div>
    </div>

    <!-- History Table -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-5">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gray-600 text-[22px]">history</span>
                <h2 class="font-semibold text-gray-900">Riwayat alert</h2>
            </div>
            <p class="mt-1 text-xs text-gray-500">Alert yang pulih tetap disimpan sebagai riwayat.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        <th class="p-4 font-semibold">Produk</th>
                        <th class="p-4 font-semibold">Lokasi</th>
                        <th class="p-4 font-semibold">Saldo / Minimum</th>
                        <th class="p-4 font-semibold">Mulai</th>
                        <th class="p-4 font-semibold">Pulih</th>
                        <th class="p-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($history as $alert)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <p class="font-semibold text-gray-900">{{ $alert->product->name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $alert->product->sku }}</p>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-gray-400 text-[16px]">location_on</span>
                                <span class="text-gray-700">{{ $alert->location->name }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="font-medium text-gray-900">{{ \App\Support\QuantityFormatter::format($alert->stock_quantity) }}</span>
                            <span class="text-gray-500"> / </span>
                            <span class="font-medium text-gray-900">{{ \App\Support\QuantityFormatter::format($alert->minimum_quantity) }}</span>
                            <span class="text-xs text-gray-500 uppercase ml-1">{{ $alert->product->unit->symbol }}</span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                <span>{{ $alert->triggered_at->format('d M Y H:i') }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            @if($alert->resolved_at)
                            <div class="flex items-center gap-1.5 text-green-600">
                                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                <span>{{ $alert->resolved_at->format('d M Y H:i') }}</span>
                            </div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold uppercase tracking-wide {{ $alert->resolved_at ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $alert->resolved_at ? 'Aman' : 'Aktif' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-gray-400 text-[32px]">history</span>
                            </div>
                            <p class="text-gray-500">Riwayat alert masih kosong.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $history->links() }}
        </div>
    </div>
</section>
@endsection
