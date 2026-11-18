@extends('layouts.dashboard')
@php $pageTitle = 'Distribusi Stok'; @endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-poppins text-xl font-semibold">Distribusi Stok</h1>
            <p class="mt-0.5 text-xs text-secondary">Permintaan outlet & pengiriman stok antar lokasi.</p>
        </div>
        @if($isPic)
            <a href="{{ route('inventory.transfers.requests.create') }}" class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white">+ Minta stok</a>
        @elseif($canManage)
            <a href="{{ route('inventory.transfers.create') }}" class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white">+ Kirim stok</a>
        @endif
    </div>

    @if(session('status'))
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="flex items-center justify-between gap-3 rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Total Transfer</p>
            <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['total'] }}</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-low"><span class="material-symbols-outlined block text-[20px] leading-none text-secondary">swap_horiz</span></span>
        </div>
        <div class="flex items-center justify-between gap-3 rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Dalam Perjalanan</p>
            <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['in_transit'] }}</p><p class="mt-1 text-xs text-secondary">{{ \App\Support\QuantityFormatter::format($stats['in_transit_quantity'] ?? 0) }} unit masih dalam kendali perusahaan</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50"><span class="material-symbols-outlined block text-[20px] leading-none text-amber-700">local_shipping</span></span>
        </div>
        <div class="flex items-center justify-between gap-3 rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Diterima</p>
            <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['received'] }}</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50"><span class="material-symbols-outlined block text-[20px] leading-none text-green-700">task_alt</span></span>
        </div>
        @if($isWarehousePic)
        <a href="{{ route('inventory.transfers.index', ['status' => 'pending_approval']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-violet-800">Menunggu Persetujuan</p><p class="mt-1.5 font-poppins text-xl font-semibold text-violet-950">{{ $stats['pending_approval'] }}</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white"><span class="material-symbols-outlined block text-[20px] leading-none text-violet-700">fact_check</span></span>
        </a>
        @endif
        <div class="flex items-center justify-between gap-3 rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Permintaan Aktif</p>
            <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['pending_requests'] }}</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10"><span class="material-symbols-outlined block text-[20px] leading-none text-primary">pending_actions</span></span>
        </div>
        <div class="flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <div class="min-w-0"><p class="text-[10px] font-semibold uppercase tracking-wider text-red-800">Discrepancy Terbuka</p>
            <p class="mt-1.5 font-poppins text-xl font-semibold text-red-900">{{ $stats['open_discrepancies'] }}</p></div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white"><span class="material-symbols-outlined block text-[20px] leading-none text-red-700">report_problem</span></span>
        </div>
    </div>

    {{-- Permintaan Outlet --}}
    @if($canManage && $requests->count())
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">⚡ Permintaan Outlet</h2>
        @foreach($requests as $transferRequest)
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-sm text-gray-800">{{ $transferRequest->reference }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $transferRequest->location->name }} · {{ $transferRequest->requester?->name ?? 'PIC Outlet' }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                    {{ $transferRequest->status === 'pending' ? 'Menunggu picking' : 'Sedang picking' }}
                </span>
            </div>
            <ul class="mt-2 space-y-0.5 text-xs text-gray-600">
                @foreach($transferRequest->items as $item)
                <li class="flex items-center gap-1"><span class="material-symbols-outlined shrink-0 text-[14px] leading-none text-gray-400">inventory_2</span>{{ $item->product->name }} — {{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}</li>
                @endforeach
            </ul>
            @if($transferRequest->status === 'pending')
                <form method="POST" action="{{ route('inventory.transfers.requests.pick', $transferRequest) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <button class="w-full rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white">Mulai picking</button>
                </form>
            @else
                <a href="{{ route('inventory.transfers.create', ['request_id' => $transferRequest->id]) }}" class="mt-3 block rounded-lg bg-primary px-3 py-2 text-center text-xs font-semibold text-white">Lanjutkan picking & kirim</a>
            @endif
        </article>
        @endforeach
        {{ $requests->links() }}
    </section>
    @endif

    {{-- Transfer List --}}
    <section class="space-y-3">
        <h2 class="font-semibold text-sm text-gray-700">Riwayat Transfer</h2>
        @forelse($transfers as $transfer)
        @php
            $statusMap = ['pending_approval' => ['label' => 'Menunggu PIC Gudang', 'class' => 'bg-violet-100 text-violet-800'], 'in_transit' => ['label' => 'Dalam perjalanan', 'class' => 'bg-amber-100 text-amber-800'],
                'partially_received'   => ['label' => 'Diterima sebagian', 'class' => 'bg-blue-100 text-blue-800'],
                'received'             => ['label' => 'Diterima', 'class' => 'bg-green-100 text-green-800'],
                'discrepancy_open' => ['label' => 'Discrepancy terbuka', 'class' => 'bg-red-100 text-red-800'],
                'received_with_discrepancy' => ['label' => 'Selesai (selisih diputuskan)', 'class' => 'bg-purple-100 text-purple-800']];
            $st = $statusMap[$transfer->status] ?? ['label' => $transfer->status, 'class' => 'bg-gray-100 text-gray-700'];
            if ($transfer->status === 'in_transit' && $transfer->expected_arrival_at?->isPast()) $st = ['label' => 'Terlambat', 'class' => 'bg-rose-100 text-rose-800'];
        @endphp
        <a href="{{ route('inventory.transfers.show', $transfer) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <p class="font-semibold text-sm text-gray-800">{{ $transfer->reference }}</p>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $st['class'] }}">{{ $st['label'] }}</span>
            </div>
            <div class="mt-2 flex items-center gap-1 text-xs text-gray-600">
                <span class="material-symbols-outlined shrink-0 text-[14px] leading-none">warehouse</span>
                {{ $transfer->sourceLocation->name }}
                <span class="material-symbols-outlined shrink-0 text-[14px] leading-none text-gray-400">arrow_forward</span>
                {{ $transfer->destinationLocation->name }}
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ $transfer->dispatched_at ? $transfer->dispatched_at->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB' : 'Menunggu persetujuan' }} · {{ $transfer->items->count() }} barang</p>
            @if($transfer->expected_arrival_at)<p class="mt-1 text-[10px] {{ $transfer->status === 'in_transit' && $transfer->expected_arrival_at->isPast() ? 'font-semibold text-rose-700' : 'text-slate-500' }}">Estimasi tiba {{ $transfer->expected_arrival_at->timezone('Asia/Jakarta')->format('d M, H:i') }} WIB</p>@endif
        </a>
        @if($isPic && $canReceive && in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy_open'], true))
            <a href="{{ route('inventory.transfers.show', $transfer) }}#receipt-form" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                <span class="material-symbols-outlined text-[18px]">fact_check</span> Konfirmasi penerimaan barang
            </a>
        @endif
        @empty
        <div class="rounded-2xl border border-outline bg-white p-8 text-center text-sm text-secondary">Belum ada transfer stok.</div>
        @endforelse
        {{ $transfers->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-2xl font-semibold">Distribusi Stok</h1>
            <p class="mt-1 text-sm text-gray-500">Permintaan outlet, picking gudang, pengiriman, dan penerimaan.</p>
        </div>
        @if($isPic)
            <a href="{{ route('inventory.transfers.requests.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[18px]">add</span> Minta Stok
            </a>
        @elseif($canManage)
            <a href="{{ route('inventory.transfers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span> Kirim Stok
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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Total Transfer</p>
                    <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['total'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-surface-container-low">
                    <span class="material-symbols-outlined text-[20px] text-secondary">swap_horiz</span>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Dalam Perjalanan</p>
                    <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['in_transit'] }}</p>
                    <p class="mt-1 text-xs text-secondary">{{ \App\Support\QuantityFormatter::format($stats['in_transit_quantity'] ?? 0) }} unit persediaan perusahaan</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                    <span class="material-symbols-outlined text-[20px] text-amber-700">local_shipping</span>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Diterima</p>
                    <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['received'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50">
                    <span class="material-symbols-outlined text-[20px] text-green-700">task_alt</span>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-outline bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-secondary">Permintaan Aktif</p>
                    <p class="mt-1.5 font-poppins text-xl font-semibold text-on-surface">{{ $stats['pending_requests'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10">
                    <span class="material-symbols-outlined text-[20px] text-primary">pending_actions</span>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-red-800">Discrepancy Terbuka</p>
                    <p class="mt-1.5 font-poppins text-xl font-semibold text-red-900">{{ $stats['open_discrepancies'] }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white">
                    <span class="material-symbols-outlined text-[20px] text-red-700">report_problem</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('inventory.transfers.index') }}" data-auto-filter class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Cari referensi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-gray-400">search</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor referensi…" class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status</label>
            <select name="status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua status</option>
                <option value="in_transit" @selected(request('status') === 'in_transit')>Dalam perjalanan</option>
                <option value="pending_approval" @selected(request('status') === 'pending_approval')>Menunggu PIC Gudang</option>
                <option value="partially_received" @selected(request('status') === 'partially_received')>Diterima sebagian</option>
                <option value="discrepancy_open" @selected(request('status') === 'discrepancy_open')>Discrepancy terbuka</option>
                <option value="received" @selected(request('status') === 'received')>Diterima</option>
                <option value="received_with_discrepancy" @selected(request('status') === 'received_with_discrepancy')>Selesai (selisih diputuskan)</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Dari tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Sampai tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.transfers.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </a>
        </div>
    </form>

    {{-- Permintaan Outlet --}}
    @if($canManage)
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-amber-600">pending_actions</span>
                <h2 class="font-semibold text-gray-800">Permintaan Outlet</h2>
                @if($stats['pending_requests'] > 0)
                    <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $stats['pending_requests'] }}</span>
                @endif
            </div>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Outlet / PIC</th>
                    <th class="px-6 py-3">Barang Diminta</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($requests as $transferRequest)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-semibold text-gray-800">{{ $transferRequest->reference }}</span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $transferRequest->requested_at?->timezone('Asia/Jakarta')->format('d M Y') ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-700">{{ $transferRequest->location->name }}</span>
                        <p class="text-xs text-gray-400">{{ $transferRequest->requester?->name }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($transferRequest->items->take(3) as $item)
                            <span class="rounded-lg bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $item->product->name }} ({{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }})</span>
                            @endforeach
                            @if($transferRequest->items->count() > 3)
                            <span class="text-xs text-gray-400">+{{ $transferRequest->items->count() - 3 }} lagi</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $transferRequest->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-orange-100 text-orange-800' }}">
                            {{ $transferRequest->status === 'pending' ? 'Menunggu picking' : 'Sedang picking' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($transferRequest->status === 'pending')
                            <form method="POST" action="{{ route('inventory.transfers.requests.pick', $transferRequest) }}">
                                @csrf @method('PATCH')
                                <button class="inline-flex items-center gap-1 rounded-lg bg-[#DC143C] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b01030]">
                                    <span class="material-symbols-outlined text-[14px]">inventory</span> Mulai picking
                                </button>
                            </form>
                        @else
                            <a href="{{ route('inventory.transfers.create', ['request_id' => $transferRequest->id]) }}" class="inline-flex items-center gap-1 rounded-lg bg-[#DC143C] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#b01030]">
                                <span class="material-symbols-outlined text-[14px]">local_shipping</span> Lanjutkan picking
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[32px] block mb-2">check_circle</span>
                        Semua permintaan outlet sudah diproses.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-6 py-3">{{ $requests->links() }}</div>
    </div>
    @endif

    {{-- Transfer History Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-gray-500">local_shipping</span>
                <h2 class="font-semibold text-gray-800">Riwayat Transfer</h2>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">{{ $transfers->total() }} transfer</span>
            </div>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Dari → Ke</th>
                    <th class="px-6 py-3">Tahap persetujuan</th>
                    <th class="px-6 py-3">Waktu Kirim</th>
                    <th class="px-6 py-3">Barang</th>
                    <th class="px-6 py-3">Status</th>
                    @if($isPic && $canReceive)<th class="px-6 py-3">Tindakan Outlet</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $statusMap = [
                        'pending_approval'       => ['label' => 'Menunggu PIC Gudang', 'class' => 'bg-violet-100 text-violet-800'],
                        'in_transit'             => ['label' => 'Dalam perjalanan',   'class' => 'bg-amber-100 text-amber-800'],
                        'partially_received'     => ['label' => 'Diterima sebagian',  'class' => 'bg-blue-100 text-blue-800'],
                        'discrepancy_open'       => ['label' => 'Discrepancy terbuka', 'class' => 'bg-red-100 text-red-800'],
                        'received'               => ['label' => 'Diterima',            'class' => 'bg-green-100 text-green-800'],
                        'received_with_discrepancy' => ['label' => 'Selesai (selisih diputuskan)', 'class' => 'bg-purple-100 text-purple-800'],
                    ];
                @endphp
                @forelse($transfers as $transfer)
                @php $st = $statusMap[$transfer->status] ?? ['label' => $transfer->status, 'class' => 'bg-gray-100 text-gray-700']; @endphp
                @if($transfer->status === 'in_transit' && $transfer->expected_arrival_at?->isPast()) @php $st = ['label' => 'Terlambat', 'class' => 'bg-rose-100 text-rose-800']; @endphp @endif
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('inventory.transfers.show', $transfer) }}" class="font-semibold text-[#DC143C] hover:underline">{{ $transfer->reference }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1 text-sm font-medium text-gray-700">
                            <span class="material-symbols-outlined text-[14px] text-gray-400">warehouse</span>
                            {{ $transfer->sourceLocation->name }}
                            <span class="material-symbols-outlined text-[14px] text-gray-400">arrow_forward</span>
                            {{ $transfer->destinationLocation->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#DC143C]/10 text-xs font-bold text-[#DC143C]">
                                {{ strtoupper(substr($transfer->preparedBy?->name ?? $transfer->dispatcher?->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <span class="text-gray-700">{{ $transfer->preparedBy?->name ?? $transfer->dispatcher?->name ?? '—' }}</span>
                                <p class="text-xs text-gray-500">Disiapkan Staff Gudang</p>
                                @if($transfer->approver)
                                    <p class="text-xs text-emerald-700">Disetujui PIC Gudang: {{ $transfer->approver->name }}</p>
                                @elseif($transfer->status === 'pending_approval')
                                    <p class="text-xs text-violet-700">Belum disetujui PIC Gudang</p>
                                @elseif($transfer->dispatched_at)
                                    <p class="text-xs text-emerald-700">Sudah dikirim dari gudang</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $transfer->dispatched_at?->timezone('Asia/Jakarta')->format('d M Y') ?? 'Menunggu persetujuan' }}
                        @if($transfer->dispatched_at)<p class="text-xs text-gray-400">{{ $transfer->dispatched_at->timezone('Asia/Jakarta')->format('H:i') }} WIB</p>@endif
                        @if($transfer->expected_arrival_at)<p class="mt-1 text-[10px] {{ $transfer->status === 'in_transit' && $transfer->expected_arrival_at->isPast() ? 'font-semibold text-rose-700' : 'text-gray-400' }}">ETA {{ $transfer->expected_arrival_at->timezone('Asia/Jakarta')->format('d M H:i') }} WIB</p>@endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $transfer->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $st['class'] }}">{{ $st['label'] }}</span>
                    </td>
                    @if($isPic && $canReceive)
                    <td class="px-6 py-4">
                        @if(in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy_open'], true))
                            <a href="{{ route('inventory.transfers.show', $transfer) }}#receipt-form" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800">
                                <span class="material-symbols-outlined text-[16px]">fact_check</span> Konfirmasi diterima
                            </a>
                        @else
                            <span class="text-xs text-gray-400">Tidak perlu tindakan</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isPic && $canReceive ? 7 : 6 }}" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2">local_shipping</span>
                        Belum ada data transfer stok.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-6 py-3">{{ $transfers->links() }}</div>
    </div>

</section>
@endsection
