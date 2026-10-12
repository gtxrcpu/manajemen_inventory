@extends('layouts.dashboard')
@php $pageTitle = 'Persetujuan — Technolife'; @endphp
@php
    $statusLabels = ['approved' => 'Disetujui', 'rejected' => 'Ditolak'];
    $statusClasses = ['approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
@endphp

@section('mobile-content')
<main class="w-full space-y-5 p-4">

    <div>
        <h1 class="font-poppins text-xl font-semibold">Persetujuan</h1>
        <p class="mt-1 text-xs text-secondary">Daftar pengajuan yang menunggu tinjauan Anda.</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        @if($canReviewRequests)
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-xs text-amber-600">PR Menunggu</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['pr_pending'] }}</p>
        </div>
        @endif
        @if($canReviewOpnames)
        <div class="rounded-2xl bg-blue-50 border border-blue-200 p-4">
            <p class="text-xs text-blue-600">Opname Menunggu</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $stats['opname_pending'] }}</p>
        </div>
        @endif
    </div>

    {{-- Pending Purchase Requests --}}
    @if($canReviewRequests)
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">
            Purchase Request
            @if($stats['pr_pending'] > 0)
                <span class="ml-1 rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $stats['pr_pending'] }}</span>
            @else
                <span class="text-xs font-normal text-gray-400">(tidak ada)</span>
            @endif
        </h2>
        @forelse($pendingRequests as $request)
        <a href="{{ route('purchasing.requests.show', $request) }}" class="block rounded-2xl border border-amber-200 bg-amber-50 p-4 hover:border-amber-400 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $request->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $request->requester->name }} · {{ $request->created_at->format('d M Y') }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-semibold text-amber-800">Menunggu</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $request->items->count() }} produk</p>
        </a>
        @empty
        <div class="rounded-xl border border-outline bg-white p-4 text-sm text-secondary">Tidak ada PR yang menunggu.</div>
        @endforelse
    </section>
    @endif

    {{-- Pending Stock Opnames --}}
    @if($canReviewOpnames)
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">
            Stock Opname
            @if($stats['opname_pending'] > 0)
                <span class="ml-1 rounded-full bg-blue-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $stats['opname_pending'] }}</span>
            @else
                <span class="text-xs font-normal text-gray-400">(tidak ada)</span>
            @endif
        </h2>
        @forelse($pendingOpnames as $opname)
        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="block rounded-2xl border border-blue-200 bg-blue-50 p-4 hover:border-blue-400 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $opname->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $opname->location->name }} · {{ $opname->counter->name }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-blue-200 px-2 py-0.5 text-[10px] font-semibold text-blue-800">Menunggu</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">{{ $opname->items->count() }} produk</p>
        </a>
        @empty
        <div class="rounded-xl border border-outline bg-white p-4 text-sm text-secondary">Tidak ada Stock Opname yang menunggu.</div>
        @endforelse
    </section>
    @endif

    {{-- Recent Decisions --}}
    <section class="space-y-2">
        <h2 class="font-semibold text-sm text-gray-700">Keputusan Terbaru</h2>
        @forelse($resolvedRequests as $request)
        <a href="{{ route('purchasing.requests.show', $request) }}" class="flex items-center justify-between rounded-xl border border-outline bg-white p-3 hover:bg-gray-50 transition-colors">
            <div>
                <p class="text-sm font-medium text-gray-800">PR · {{ $request->reference }}</p>
                <p class="mt-0.5 text-[11px] text-gray-400">{{ $request->reviewed_at?->format('d M Y H:i') }} · {{ $request->reviewer?->name ?? '—' }}</p>
            </div>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$request->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statusLabels[$request->status] }}</span>
        </a>
        @empty @endforelse
        @forelse($resolvedOpnames as $opname)
        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="flex items-center justify-between rounded-xl border border-outline bg-white p-3 hover:bg-gray-50 transition-colors">
            <div>
                <p class="text-sm font-medium text-gray-800">Opname · {{ $opname->reference }}</p>
                <p class="mt-0.5 text-[11px] text-gray-400">{{ $opname->reviewed_at?->format('d M Y H:i') }} · {{ $opname->reviewer?->name ?? '—' }}</p>
            </div>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$opname->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statusLabels[$opname->status] }}</span>
        </a>
        @empty @endforelse
        @if($resolvedRequests->isEmpty() && $resolvedOpnames->isEmpty())
        <div class="rounded-xl border border-outline bg-white p-4 text-sm text-secondary">Belum ada keputusan yang tercatat.</div>
        @endif
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="font-poppins text-2xl font-semibold">Persetujuan</h1>
        <p class="mt-1 text-sm text-gray-500">Tinjau pengajuan sesuai kewenangan role Anda.</p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @if($canReviewRequests)
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-amber-600 uppercase tracking-wide">PR Menunggu</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['pr_pending'] }}</p>
                    <p class="mt-1 text-xs text-amber-500">Butuh ditinjau</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-200">
                    <span class="material-symbols-outlined text-[22px] text-amber-700">request_quote</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-green-100 bg-green-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-green-600 uppercase tracking-wide">PR Disetujui</p>
                    <p class="mt-2 text-3xl font-bold text-green-700">{{ $stats['pr_approved'] }}</p>
                    <p class="mt-1 text-xs text-green-500">Total disetujui</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-200">
                    <span class="material-symbols-outlined text-[22px] text-green-700">thumb_up</span>
                </div>
            </div>
        </div>
        @endif
        @if($canReviewOpnames)
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Opname Menunggu</p>
                    <p class="mt-2 text-3xl font-bold text-blue-700">{{ $stats['opname_pending'] }}</p>
                    <p class="mt-1 text-xs text-blue-500">Butuh ditinjau</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-200">
                    <span class="material-symbols-outlined text-[22px] text-blue-700">inventory_2</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Opname Disetujui</p>
                    <p class="mt-2 text-3xl font-bold text-gray-800">{{ $stats['opname_approved'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">Total disetujui</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100">
                    <span class="material-symbols-outlined text-[22px] text-gray-600">task_alt</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Pending Purchase Requests Table --}}
    @if($canReviewRequests)
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4">
            <span class="material-symbols-outlined text-[20px] text-amber-600">request_quote</span>
            <h2 class="font-semibold text-gray-800">Purchase Request</h2>
            @if($stats['pr_pending'] > 0)
                <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $stats['pr_pending'] }} menunggu</span>
            @else
                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">Tidak ada</span>
            @endif
        </div>
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Pemohon</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3">Tgl. Dibutuhkan</th>
                    <th class="px-6 py-3">Dikirim</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pendingRequests as $request)
                <tr class="hover:bg-amber-50/30 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-800">{{ $request->reference }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#DC143C]/10 text-xs font-bold text-[#DC143C]">
                                {{ strtoupper(substr($request->requester->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-700">{{ $request->requester->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $request->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $request->required_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $request->created_at->format('d M Y') }}
                        <p class="text-xs text-gray-400">{{ $request->created_at->format('H:i') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('purchasing.requests.show', $request) }}" class="inline-flex items-center gap-1 rounded-lg bg-[#DC143C] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b01030]">
                            <span class="material-symbols-outlined text-[14px]">rate_review</span> Tinjau
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[36px] block mb-2">check_circle</span>
                        Tidak ada PR yang menunggu persetujuan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    {{-- Pending Stock Opname Table --}}
    @if($canReviewOpnames)
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4">
            <span class="material-symbols-outlined text-[20px] text-blue-600">inventory_2</span>
            <h2 class="font-semibold text-gray-800">Stock Opname</h2>
            @if($stats['opname_pending'] > 0)
                <span class="rounded-full bg-blue-500 px-2 py-0.5 text-xs font-bold text-white">{{ $stats['opname_pending'] }} menunggu</span>
            @else
                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">Tidak ada</span>
            @endif
        </div>
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Lokasi</th>
                    <th class="px-6 py-3">Petugas</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3">Dikirim</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pendingOpnames as $opname)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-800">{{ $opname->reference }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 text-gray-700">
                            <span class="material-symbols-outlined text-[15px] text-gray-400">location_on</span>
                            {{ $opname->location->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                {{ strtoupper(substr($opname->counter->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-700">{{ $opname->counter->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $opname->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $opname->submitted_at?->format('d M Y') ?? '—' }}
                        @if($opname->submitted_at)
                            <p class="text-xs text-gray-400">{{ $opname->submitted_at->format('H:i') }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="inline-flex items-center gap-1 rounded-lg bg-[#DC143C] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b01030]">
                            <span class="material-symbols-outlined text-[14px]">rate_review</span> Tinjau
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[36px] block mb-2">check_circle</span>
                        Tidak ada Stock Opname yang menunggu persetujuan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    {{-- Recent Decisions --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
            <span class="material-symbols-outlined text-[20px] text-gray-500">history</span>
            <h2 class="font-semibold text-gray-800">Riwayat Keputusan Terbaru</h2>
        </div>
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Jenis / Referensi</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Diputuskan oleh</th>
                    <th class="px-6 py-3">Waktu</th>
                    <th class="px-6 py-3">Catatan</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($resolvedRequests as $request)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">PR</span>
                            <span class="font-medium text-gray-800">{{ $request->reference }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$request->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$request->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $request->reviewer?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500 text-xs">{{ $request->reviewed_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500 max-w-[200px] truncate">{{ $request->review_note ?: '—' }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('purchasing.requests.show', $request) }}" class="text-xs font-semibold text-[#DC143C] hover:underline">Detail</a>
                    </td>
                </tr>
                @empty @endforelse
                @forelse($resolvedOpnames as $opname)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Opname</span>
                            <span class="font-medium text-gray-800">{{ $opname->reference }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$opname->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$opname->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $opname->reviewer?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500 text-xs">{{ $opname->reviewed_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500 max-w-[200px] truncate">{{ $opname->review_note ?: '—' }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="text-xs font-semibold text-[#DC143C] hover:underline">Detail</a>
                    </td>
                </tr>
                @empty @endforelse
                @if($resolvedRequests->isEmpty() && $resolvedOpnames->isEmpty())
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[36px] block mb-2">history</span>
                        Belum ada keputusan yang tercatat.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

</section>
@endsection
