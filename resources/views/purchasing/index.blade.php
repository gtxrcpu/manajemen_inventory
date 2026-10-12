@extends('layouts.dashboard')
@php $pageTitle = 'Pengadaan — Technolife'; @endphp
@php
    $statusLabels = [
        'pending_approval' => 'Menunggu persetujuan',
        'approved'         => 'Disetujui',
        'rejected'         => 'Ditolak',
        'ordered'          => 'PO diterbitkan',
        'issued'           => 'Menunggu barang',
        'received'         => 'Diterima',
    ];
    $statusClasses = [
        'pending_approval' => 'bg-amber-100 text-amber-800',
        'approved'         => 'bg-blue-100 text-blue-800',
        'rejected'         => 'bg-red-100 text-red-800',
        'ordered'          => 'bg-indigo-100 text-indigo-800',
        'issued'           => 'bg-orange-100 text-orange-800',
        'received'         => 'bg-green-100 text-green-800',
    ];
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="font-poppins text-xl font-semibold">Pengadaan</h1>
            <p class="mt-0.5 text-xs text-secondary">Pantau permintaan pembelian, PO, dan penerimaan.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.requests.create') }}" class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white">+ Buat PR</a>
        @endif
    </div>

    @if(session('status'))
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white border border-outline p-4">
            <p class="text-xs text-gray-500">Total PR</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stats['pr_total'] }}</p>
            <p class="text-[10px] text-amber-600 mt-0.5">{{ $stats['pr_pending'] }} menunggu</p>
        </div>
        <div class="rounded-2xl bg-white border border-outline p-4">
            <p class="text-xs text-gray-500">Total PO</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stats['po_total'] }}</p>
            <p class="text-[10px] text-orange-600 mt-0.5">{{ $stats['po_pending'] }} aktif</p>
        </div>
    </div>

    {{-- Purchase Request --}}
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">Purchase Request</h2>
        @forelse($requests as $pr)
        <a href="{{ route('purchasing.requests.show', $pr) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $pr->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $pr->requester->name }} · {{ $pr->created_at->format('d M Y') }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$pr->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statusLabels[$pr->status] ?? $pr->status }}</span>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ $pr->items->count() }} produk · Dibutuhkan: {{ $pr->required_date?->format('d M Y') ?? '—' }}</p>
        </a>
        @empty
        <div class="rounded-xl border border-outline bg-white p-5 text-sm text-secondary">Belum ada Purchase Request.</div>
        @endforelse
        {{ $requests->links() }}
    </section>

    {{-- Purchase Order --}}
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">Purchase Order</h2>
        @forelse($orders as $order)
        <a href="{{ route('purchasing.orders.show', $order) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $order->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $order->supplier->name }} · {{ $order->location->name }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ $order->items->count() }} produk · Jadwal tiba: {{ $order->expected_delivery?->format('d M Y') ?? '—' }}</p>
        </a>
        @empty
        <div class="rounded-xl border border-outline bg-white p-5 text-sm text-secondary">Belum ada Purchase Order.</div>
        @endforelse
        {{ $orders->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-2xl font-semibold">Pengadaan</h1>
            <p class="mt-1 text-sm text-gray-500">Alur Purchase Request, persetujuan, Purchase Order, dan penerimaan barang.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.requests.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[18px]">add</span> Buat Purchase Request
            </a>
        @endif
    </div>

    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            <span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span>
            {{ session('status') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total PR</p>
                    <p class="mt-2 text-3xl font-bold text-gray-800">{{ $stats['pr_total'] }}</p>
                    <p class="mt-1 text-xs text-amber-600">{{ $stats['pr_pending'] }} menunggu</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100">
                    <span class="material-symbols-outlined text-[22px] text-blue-600">request_quote</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">PR Disetujui</p>
                    <p class="mt-2 text-3xl font-bold text-blue-700">{{ $stats['pr_approved'] }}</p>
                    <p class="mt-1 text-xs text-blue-500">Siap dibuat PO</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-200">
                    <span class="material-symbols-outlined text-[22px] text-blue-700">thumb_up</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-orange-600 uppercase tracking-wide">PO Aktif</p>
                    <p class="mt-2 text-3xl font-bold text-orange-700">{{ $stats['po_pending'] }}</p>
                    <p class="mt-1 text-xs text-orange-500">Menunggu barang</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-200">
                    <span class="material-symbols-outlined text-[22px] text-orange-700">package_2</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-green-100 bg-green-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-green-600 uppercase tracking-wide">PO Diterima</p>
                    <p class="mt-2 text-3xl font-bold text-green-700">{{ $stats['po_received'] }}</p>
                    <p class="mt-1 text-xs text-green-500">Selesai</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-200">
                    <span class="material-symbols-outlined text-[22px] text-green-700">task_alt</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="flex-1 min-w-[220px]">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Cari referensi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-gray-400">search</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor referensi PR / PO…" class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status PR</label>
            <select name="pr_status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua</option>
                @foreach($statusLabels as $val => $label)
                    <option value="{{ $val }}" @selected(request('pr_status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status PO</label>
            <select name="po_status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua</option>
                <option value="ordered" @selected(request('po_status') === 'ordered')>PO diterbitkan</option>
                <option value="issued" @selected(request('po_status') === 'issued')>Menunggu barang</option>
                <option value="received" @selected(request('po_status') === 'received')>Diterima</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#DC143C] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[16px]">filter_list</span> Filter
            </button>
            <a href="{{ route('purchasing.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </a>
        </div>
    </form>

    {{-- Purchase Request Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-blue-600">request_quote</span>
                <h2 class="font-semibold text-gray-800">Purchase Request</h2>
                <span class="rounded-full bg-blue-500 px-2 py-0.5 text-xs font-bold text-white">{{ $requests->total() }}</span>
            </div>
        </div>
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Pemohon</th>
                    <th class="px-6 py-3">Jumlah Produk</th>
                    <th class="px-6 py-3">Tgl. Dibutuhkan</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($requests as $pr)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-800">{{ $pr->reference }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $pr->created_at->format('d M Y') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#DC143C]/10 text-xs font-bold text-[#DC143C]">
                                {{ strtoupper(substr($pr->requester->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-700">{{ $pr->requester->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $pr->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $pr->required_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$pr->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$pr->status] ?? $pr->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('purchasing.requests.show', $pr) }}" class="inline-flex items-center gap-1 font-semibold text-[#DC143C] hover:underline text-xs">
                            Detail <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2">request_quote</span>
                        Belum ada Purchase Request.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-6 py-3">{{ $requests->links() }}</div>
    </div>

    {{-- Purchase Order Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-orange-600">package_2</span>
                <h2 class="font-semibold text-gray-800">Purchase Order</h2>
                <span class="rounded-full bg-orange-500 px-2 py-0.5 text-xs font-bold text-white">{{ $orders->total() }}</span>
            </div>
        </div>
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Supplier</th>
                    <th class="px-6 py-3">Lokasi</th>
                    <th class="px-6 py-3">Jadwal Tiba</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-800">{{ $order->reference }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-gray-400">storefront</span>
                            <span class="text-gray-700">{{ $order->supplier->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 text-gray-600">
                            <span class="material-symbols-outlined text-[16px] text-gray-400">location_on</span>
                            {{ $order->location->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        @if($order->expected_delivery)
                            <span class="{{ $order->expected_delivery->isPast() && $order->status !== 'received' ? 'text-red-600 font-semibold' : '' }}">
                                {{ $order->expected_delivery->format('d M Y') }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $order->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$order->status] ?? $order->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('purchasing.orders.show', $order) }}" class="inline-flex items-center gap-1 font-semibold text-[#DC143C] hover:underline text-xs">
                            Detail <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2">package_2</span>
                        Belum ada Purchase Order.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-6 py-3">{{ $orders->links() }}</div>
    </div>

</section>
@endsection
