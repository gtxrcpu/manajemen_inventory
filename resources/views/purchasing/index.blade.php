@extends('layouts.dashboard')
@php $pageTitle = 'Pengadaan — Technolife'; @endphp
@php
    $statusLabels = [
        'pending_approval' => 'Menunggu persetujuan',
        'approved'         => 'Disetujui otomatis',
        'rejected'         => 'Dibatalkan',
        'ordered'          => 'PO diterbitkan',
        'issued'           => 'Menunggu barang',
        'received'         => 'Diterima',
    ];
    $poStatusLabels = ['closed_with_variance' => 'Ditutup dengan selisih'];
    $statusClasses = [
        'pending_approval' => 'bg-amber-100 text-amber-800',
        'approved'         => 'bg-blue-100 text-blue-800',
        'rejected'         => 'bg-red-100 text-red-800',
        'ordered'          => 'bg-indigo-100 text-indigo-800',
        'issued'           => 'bg-orange-100 text-orange-800',
        'received'         => 'bg-green-100 text-green-800',
        'closed_with_variance' => 'bg-violet-100 text-violet-800',
    ];
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">

    <div class="space-y-3">
        <div>
            <h1 class="font-poppins text-xl font-semibold text-on-surface">Pengadaan</h1>
            <p class="mt-0.5 text-xs text-secondary">Permintaan pembelian dan pesanan ke supplier.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermissionTo('purchasing.manage'))
                <a href="{{ route('purchasing.requests.create') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-primary px-3.5 text-xs font-semibold text-white shadow-sm">
                    <span class="material-symbols-outlined text-[17px] leading-none">add</span>Buat PR
                </a>
            @endif
            @if(auth()->user()->hasPermissionTo('invoice.view'))
                <a href="{{ route('purchase-invoices.index') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-outline bg-white px-3.5 text-xs font-semibold text-gray-700">
                    <span class="material-symbols-outlined text-[17px] leading-none">receipt_long</span>Faktur Pembelian
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    @if($stats['po_follow_up'] > 0)
        <a href="{{ route('purchasing.index', ['po_status' => 'follow_up']) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3.5 text-amber-950">
            <span class="flex min-w-0 items-center gap-2.5"><span class="material-symbols-outlined text-amber-700">priority_high</span><span class="text-xs leading-snug"><strong class="font-semibold">{{ $stats['po_follow_up'] }} PO perlu ditindaklanjuti</strong><span class="mt-0.5 block text-amber-800">Penerimaan sebagian, cek jadwal supplier.</span></span></span>
            <span class="material-symbols-outlined shrink-0 text-[18px]">arrow_forward</span>
        </a>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-2.5">
        <div class="rounded-2xl border border-[#EAEAEA] bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary"><span class="text-[10px] font-semibold uppercase tracking-wider">Total PR</span><span class="material-symbols-outlined text-[18px] text-primary">request_quote</span></div>
            <p class="mt-1 font-poppins text-lg font-semibold text-on-surface">{{ $stats['pr_total'] }}</p>
            <p class="mt-0.5 text-[10px] text-secondary">{{ $stats['pr_approved'] }} siap dibuat PO</p>
        </div>
        <div class="rounded-2xl border border-[#EAEAEA] bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary"><span class="text-[10px] font-semibold uppercase tracking-wider">PR Disetujui</span><span class="material-symbols-outlined text-[18px] text-blue-600">thumb_up</span></div>
            <p class="mt-1 font-poppins text-lg font-semibold text-on-surface">{{ $stats['pr_approved'] }}</p>
            <p class="mt-0.5 text-[10px] text-secondary">Siap dibuat PO</p>
        </div>
        <div class="rounded-2xl border border-[#EAEAEA] bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary"><span class="text-[10px] font-semibold uppercase tracking-wider">PO Aktif</span><span class="material-symbols-outlined text-[18px] text-amber-600">package_2</span></div>
            <p class="mt-1 font-poppins text-lg font-semibold text-on-surface">{{ $stats['po_pending'] }}</p>
            <p class="mt-0.5 text-[10px] text-secondary">{{ $stats['po_follow_up'] }} penerimaan parsial perlu dipantau</p>
        </div>
        <div class="rounded-2xl border border-[#EAEAEA] bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-secondary"><span class="text-[10px] font-semibold uppercase tracking-wider">PO Diterima</span><span class="material-symbols-outlined text-[18px] text-emerald-600">task_alt</span></div>
            <p class="mt-1 font-poppins text-lg font-semibold text-on-surface">{{ $stats['po_received'] }}</p>
            <p class="mt-0.5 text-[10px] text-secondary">Selesai</p>
        </div>
    </div>

    <details class="group overflow-hidden rounded-2xl border border-outline bg-white shadow-2xs">
        <summary class="flex cursor-pointer list-none items-center justify-between p-3.5 text-xs font-semibold text-on-surface">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-primary">tune</span>Filter &amp; pencarian</span>
            <span class="material-symbols-outlined text-[18px] text-secondary transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <form method="GET" data-purchasing-filter-form class="space-y-2.5 border-t border-outline/50 p-3.5">
            <input type="search" name="q" data-purchasing-filter-search value="{{ request('q') }}" placeholder="Cari referensi, supplier, lokasi, atau produk" class="h-10 w-full rounded-xl border border-outline bg-surface-container-lowest px-3 text-xs text-on-surface outline-none focus:border-primary">
            <div class="grid grid-cols-2 gap-2">
                <select name="pr_status" class="h-9 min-w-0 rounded-xl border border-outline bg-surface-container-lowest px-2 text-xs text-on-surface">
                    <option value="">Semua status PR</option>
                    @foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('pr_status') === $value)>{{ $label }}</option>@endforeach
                </select>
                <select name="po_status" class="h-9 min-w-0 rounded-xl border border-outline bg-surface-container-lowest px-2 text-xs text-on-surface">
                    <option value="">Semua status PO</option>
                    <option value="active" @selected(request('po_status') === 'active')>PO aktif</option>
                    <option value="follow_up" @selected(request('po_status') === 'follow_up')>Perlu tindak lanjut</option>
                    <option value="ordered" @selected(request('po_status') === 'ordered')>PO diterbitkan</option>
                    <option value="issued" @selected(request('po_status') === 'issued')>Menunggu barang</option>
                    <option value="received" @selected(request('po_status') === 'received')>Diterima</option>
                    <option value="closed_with_variance" @selected(request('po_status') === 'closed_with_variance')>Ditutup dengan selisih</option>
                </select>
            </div>
            @if(request()->filled('q') || request()->filled('pr_status') || request()->filled('po_status'))
                <div class="flex justify-end"><a href="{{ route('purchasing.index') }}" class="text-xs font-medium text-secondary underline decoration-gray-300 underline-offset-2">Reset filter</a></div>
            @endif
        </form>
    </details>

    {{-- Purchase Request --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between"><h2 class="font-semibold text-sm text-gray-800">Purchase Request</h2><span class="text-xs text-gray-500">{{ $requests->total() }} hasil</span></div>
        @forelse($requests as $pr)
        <a data-purchasing-search-item href="{{ $pr->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage') ? route('purchasing.orders.create', $pr) : route('purchasing.requests.show', $pr) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
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
        <div class="rounded-xl border border-outline bg-white p-5 text-sm text-secondary">
            @if(request()->filled('q') || request()->filled('pr_status'))
                <p>Tidak ada Purchase Request yang cocok dengan filter.</p><a href="{{ route('purchasing.index') }}" class="mt-2 inline-block font-semibold text-primary">Hapus filter</a>
            @else
                Belum ada Purchase Request.
            @endif
        </div>
        @endforelse
        {{ $requests->links() }}
    </section>

    {{-- Purchase Order --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between"><h2 class="font-semibold text-sm text-gray-800">Purchase Order</h2><span class="text-xs text-gray-500">{{ $orders->total() }} hasil</span></div>
        @forelse($orders as $order)
        <a data-purchasing-search-item href="{{ route('purchasing.orders.show', $order) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $order->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $order->supplier->name }} · {{ $order->location->name }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $order->status === 'issued' && $order->stockIn->isNotEmpty() ? ($order->supplier_follow_up_action ? 'Tindak lanjut supplier' : 'Perlu tindak lanjut') : ($poStatusLabels[$order->status] ?? $statusLabels[$order->status] ?? $order->status) }}</span>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ $order->items->count() }} produk · Jadwal tiba: {{ $order->expected_delivery?->format('d M Y') ?? '—' }}</p>
        </a>
        @empty
        <div class="rounded-xl border border-outline bg-white p-5 text-sm text-secondary">
            @if(request()->filled('q') || request()->filled('po_status'))
                <p>Tidak ada Purchase Order yang cocok dengan filter.</p><a href="{{ route('purchasing.index') }}" class="mt-2 inline-block font-semibold text-primary">Hapus filter</a>
            @else
                Belum ada Purchase Order.
            @endif
        </div>
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
            <p class="mt-1 text-sm text-gray-500">PR disetujui otomatis; Purchasing menerbitkan PO dan Staff Gudang memeriksa penerimaan.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.requests.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[18px]">add</span> Buat Purchase Request
            </a>
        @endif
        @if(auth()->user()->hasPermissionTo('invoice.view'))
            <a href="{{ route('purchase-invoices.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"><span class="material-symbols-outlined text-[18px]">receipt_long</span> Faktur Pembelian</a>
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
        <div class="rounded-xl border border-[#EAEAEA] bg-white p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)]">
            <div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total PR</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-primary"><span class="material-symbols-outlined text-[20px]">request_quote</span></span></div>
            <p class="mt-2 font-poppins text-2xl font-semibold leading-tight text-gray-900">{{ $stats['pr_total'] }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $stats['pr_approved'] }} siap dibuat PO</p>
        </div>
        <div class="rounded-xl border border-[#EAEAEA] bg-white p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)]">
            <div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">PR Disetujui</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><span class="material-symbols-outlined text-[20px]">thumb_up</span></span></div>
            <p class="mt-2 font-poppins text-2xl font-semibold leading-tight text-gray-900">{{ $stats['pr_approved'] }}</p>
            <p class="mt-1 text-xs text-gray-400">Siap dibuat PO</p>
        </div>
        <div class="rounded-xl border border-[#EAEAEA] bg-white p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)]">
            <div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">PO Aktif</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><span class="material-symbols-outlined text-[20px]">package_2</span></span></div>
            <p class="mt-2 font-poppins text-2xl font-semibold leading-tight text-gray-900">{{ $stats['po_pending'] }}</p>
            <p class="mt-1 text-xs text-gray-400">Menunggu barang</p>
        </div>
        <div class="rounded-xl border border-[#EAEAEA] bg-white p-4.5 shadow-[0_4px_12px_rgba(0,0,0,0.03)]">
            <div class="flex items-center justify-between"><span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">PO Diterima</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><span class="material-symbols-outlined text-[20px]">task_alt</span></span></div>
            <p class="mt-2 font-poppins text-2xl font-semibold leading-tight text-gray-900">{{ $stats['po_received'] }}</p>
            <p class="mt-1 text-xs text-gray-400">Selesai</p>
        </div>
    </div>

    @if($stats['po_follow_up'] > 0)
        <a href="{{ route('purchasing.index', ['po_status' => 'follow_up']) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-amber-950 transition-colors hover:bg-amber-100 sm:px-5">
            <span class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700"><span class="material-symbols-outlined">priority_high</span></span><span class="min-w-0"><span class="block text-sm font-semibold">{{ $stats['po_follow_up'] }} PO perlu tindak lanjut supplier</span><span class="mt-0.5 block text-xs text-amber-800">Ada penerimaan sebagian. Buka daftar untuk cek janji pengiriman berikutnya.</span></span></span>
            <span class="shrink-0 text-xs font-semibold">Buka daftar <span aria-hidden="true">→</span></span>
        </a>
    @endif

    {{-- Filter Toolbar --}}
    <form method="GET" data-purchasing-filter-form class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="flex-1 min-w-[220px]">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Cari referensi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-gray-400">search</span>
                <input type="search" name="q" data-purchasing-filter-search value="{{ request('q') }}" placeholder="Cari referensi, supplier, lokasi, atau produk…" class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status PR</label>
            <select name="pr_status" data-purchasing-filter-select class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua</option>
                @foreach($statusLabels as $val => $label)
                    <option value="{{ $val }}" @selected(request('pr_status') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status PO</label>
            <select name="po_status" data-purchasing-filter-select class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua</option>
                <option value="active" @selected(request('po_status') === 'active')>PO aktif</option>
                <option value="follow_up" @selected(request('po_status') === 'follow_up')>Perlu tindak lanjut</option>
                <option value="ordered" @selected(request('po_status') === 'ordered')>PO diterbitkan</option>
                <option value="issued" @selected(request('po_status') === 'issued')>Menunggu / penerimaan sebagian</option>
                <option value="received" @selected(request('po_status') === 'received')>Diterima</option>
                <option value="closed_with_variance" @selected(request('po_status') === 'closed_with_variance')>Ditutup dengan selisih</option>
            </select>
        </div>
        @if(request()->filled('q') || request()->filled('pr_status') || request()->filled('po_status'))
            <a href="{{ route('purchasing.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Reset filter</a>
        @endif
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
                <tr data-purchasing-search-item class="hover:bg-gray-50/50 transition-colors">
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
                        @if($pr->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
                            <a href="{{ route('purchasing.orders.create', $pr) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-[#DC143C] px-3 py-2 font-semibold text-white transition-colors hover:bg-[#b01030] text-xs">
                                Buat PO <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        @else
                            <a href="{{ route('purchasing.requests.show', $pr) }}" class="inline-flex items-center gap-1 font-semibold text-[#DC143C] hover:underline text-xs">
                                Detail <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        @endif
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
                <tr data-purchasing-search-item class="hover:bg-gray-50/50 transition-colors">
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
                            @php
                                $deliveryOverdue = $order->expected_delivery->lt(today()) && ! in_array($order->status, ['received', 'closed_with_variance'], true);
                            @endphp
                            <span class="{{ $deliveryOverdue ? 'text-red-700 font-semibold' : 'text-gray-600' }}">{{ $order->expected_delivery->format('d M Y') }}</span>
                            @if($deliveryOverdue)<span class="mt-1 block text-[10px] font-semibold text-red-600">Lewat {{ $order->expected_delivery->diffInDays(today()) }} hari</span>@endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $order->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $order->status === 'issued' && $order->stockIn->isNotEmpty() ? ($order->supplier_follow_up_action ? 'Tindak lanjut supplier' : 'Perlu tindak lanjut') : ($poStatusLabels[$order->status] ?? $statusLabels[$order->status] ?? $order->status) }}
                        </span>
                        @if($order->supplier_follow_up_due_at && $order->status === 'issued')
                            <span class="mt-1 block text-[10px] text-gray-500">Tagih supplier: {{ $order->supplier_follow_up_due_at->format('d M Y') }}</span>
                        @endif
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

@section('page-scripts')
    (() => {
        const searchItems = document.querySelectorAll('[data-purchasing-search-item]');
        const applyInstantSearch = value => {
            const query = value.trim().toLocaleLowerCase('id-ID');
            searchItems.forEach(item => {
                item.style.display = !query || item.innerText.toLocaleLowerCase('id-ID').includes(query) ? '' : 'none';
            });
        };

        document.querySelectorAll('[data-purchasing-filter-form]').forEach(form => {
            const search = form.querySelector('[data-purchasing-filter-search]');

            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', () => {
                    form.requestSubmit();
                });
            });

            if (search) {
                search.addEventListener('input', () => applyInstantSearch(search.value));
                applyInstantSearch(search.value);
            }
        });
    })();
@endsection
