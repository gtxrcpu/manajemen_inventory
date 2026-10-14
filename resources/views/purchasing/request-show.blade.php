@extends('layouts.dashboard')

@php
    $pageTitle = 'Detail Purchase Request';
    $statusLabels = [
        'pending_approval' => 'Menunggu persetujuan',
        'approved' => 'Disetujui otomatis',
        'rejected' => 'Dibatalkan',
        'ordered' => 'PO sudah diterbitkan',
    ];
    $statusStyles = [
        'pending_approval' => 'bg-amber-50 text-amber-800',
        'approved' => 'bg-green-50 text-green-800',
        'rejected' => 'bg-red-50 text-red-800',
        'ordered' => 'bg-blue-50 text-blue-800',
    ];
    $relatedOrder = $purchaseRequest->purchaseOrder->first();
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a href="{{ route('purchasing.index') }}" class="text-sm font-semibold text-primary">&larr; Kembali ke Pengadaan</a>

    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-800">
            <span class="material-symbols-outlined text-[18px] text-green-600 shrink-0">check_circle</span>
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-2xl border border-outline bg-white p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs text-secondary">Purchase Request</p>
                <h1 class="mt-1 text-xl font-semibold">{{ $purchaseRequest->reference }}</h1>
            </div>
            <span class="rounded-full px-3 py-1 text-center text-xs font-semibold {{ $statusStyles[$purchaseRequest->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-4">
            {{-- Mini Stat Card 1: Pemohon --}}
            <div class="rounded-2xl bg-white border border-outline p-3.5">
                <p class="text-xs text-secondary">Pemohon</p>
                <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $purchaseRequest->requester->name }}</p>
                <p class="text-[10px] text-secondary mt-1">{{ $purchaseRequest->created_at->format('d M Y') }}</p>
            </div>

            {{-- Mini Stat Card 2: Tanggal Dibutuhkan --}}
            <div class="rounded-2xl bg-white border border-outline p-3.5">
                <p class="text-xs text-secondary">Dibutuhkan</p>
                <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $purchaseRequest->required_date?->format('d M') ?? '—' }}</p>
                <p class="text-[10px] text-secondary mt-1">{{ $purchaseRequest->required_date?->format('Y') ?? 'TBD' }}</p>
            </div>
        </div>

        @if ($purchaseRequest->notes)
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-3">
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-[16px] text-amber-600 shrink-0">sticky_note_2</span>
                    <div>
                        <p class="text-xs font-semibold text-amber-900">Catatan pemohon</p>
                        <p class="mt-1 whitespace-pre-line text-xs text-amber-800">{{ $purchaseRequest->notes }}</p>
                    </div>
                </div>
            </div>
        @endif
        @if ($purchaseRequest->review_note)
            <div class="mt-3 rounded-xl bg-gray-50 border border-gray-200 p-3">
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-[16px] text-gray-600 shrink-0">comment</span>
                    <div>
                        <p class="text-xs font-semibold text-gray-900">Catatan keputusan</p>
                        <p class="mt-1 whitespace-pre-line text-xs text-gray-600">{{ $purchaseRequest->review_note }}</p>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-outline bg-white p-4">
        <h2 class="font-semibold text-sm">Barang yang diminta</h2>
        <div class="space-y-2 mt-3">
            @foreach ($purchaseRequest->items as $item)
                <div class="rounded-2xl bg-white border border-outline p-3.5 flex items-start gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 shrink-0">
                        <span class="material-symbols-outlined text-[16px] text-gray-600">package_2</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $item->product->name }}</p>
                        <p class="text-xs text-secondary truncate">{{ $item->product->sku }}</p>
                        <div class="mt-2 rounded-lg bg-gray-50 px-2 py-1.5">
                            <p class="text-xs font-semibold text-gray-700">{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->product->unit->symbol }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($purchaseRequest->status === 'approved')
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">PR disetujui otomatis saat dikirim dan siap diproses oleh Purchasing.</p>
    @endif

    @if ($relatedOrder)
        <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="flex items-center justify-center gap-2 rounded-xl border border-outline bg-white p-3 text-center text-sm font-semibold">
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            Lihat PO {{ $relatedOrder->reference }}
        </a>
    @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="flex items-center justify-center gap-2 rounded-xl bg-primary p-3 text-center text-sm font-semibold text-white">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Buat Purchase Order
        </a>
    @endif
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('purchasing.index') }}" class="text-sm font-semibold text-[#DC143C]">&larr; Kembali ke Pengadaan</a>

    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            <span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span>
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-100 bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs uppercase text-gray-500">Purchase Request</p><h1 class="mt-1 text-2xl font-semibold">{{ $purchaseRequest->reference }}</h1></div>
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $statusStyles[$purchaseRequest->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}</span>
        </div>

        {{-- KPI Stat Cards (4 columns) --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mt-5">
            {{-- Card 1: Status --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</p>
                        <p class="mt-2 text-xl font-bold text-gray-800">{{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $purchaseRequest->status === 'approved' ? 'bg-green-100' : ($purchaseRequest->status === 'pending_approval' ? 'bg-amber-100' : ($purchaseRequest->status === 'rejected' ? 'bg-red-100' : 'bg-blue-100')) }}">
                        <span class="material-symbols-outlined text-[22px] {{ $purchaseRequest->status === 'approved' ? 'text-green-600' : ($purchaseRequest->status === 'pending_approval' ? 'text-amber-600' : ($purchaseRequest->status === 'rejected' ? 'text-red-600' : 'text-blue-600')) }}">{{ $purchaseRequest->status === 'approved' ? 'check_circle' : ($purchaseRequest->status === 'rejected' ? 'cancel' : 'schedule') }}</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Pemohon --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pemohon</p>
                        <p class="mt-2 text-lg font-bold text-gray-800">{{ $purchaseRequest->requester->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->requester->email ?? '—' }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100">
                        <span class="material-symbols-outlined text-[22px] text-blue-600">person</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Tanggal Dibutuhkan --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Dibutuhkan</p>
                        <p class="mt-2 text-xl font-bold text-gray-800">{{ $purchaseRequest->required_date?->format('d M Y') ?? '—' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->required_date ? $purchaseRequest->required_date->diffForHumans() : 'Belum ditentukan' }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-100">
                        <span class="material-symbols-outlined text-[22px] text-orange-600">calendar_month</span>
                    </div>
                </div>
            </div>

            {{-- Card 4: Jumlah Produk --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Jumlah Produk</p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">{{ $purchaseRequest->items->count() }}</p>
                        <p class="mt-1 text-xs text-gray-500">jenis produk</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100">
                        <span class="material-symbols-outlined text-[22px] text-indigo-600">package_2</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($purchaseRequest->notes)
            <div class="mt-5 rounded-xl bg-amber-50 border border-amber-200 p-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[20px] text-amber-600 shrink-0">sticky_note_2</span>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Catatan pemohon</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-amber-800">{{ $purchaseRequest->notes }}</p>
                    </div>
                </div>
            </div>
        @endif
        @if ($purchaseRequest->review_note)
            <div class="mt-4 rounded-xl bg-gray-50 border border-gray-200 p-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[20px] text-gray-600 shrink-0">comment</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Catatan keputusan</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $purchaseRequest->review_note }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <section>
        <h2 class="text-lg font-semibold mb-4">Barang yang diminta</h2>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($purchaseRequest->items as $item)
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 shrink-0">
                            <span class="material-symbols-outlined text-[18px] text-gray-600">package_2</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 truncate">{{ $item->product->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $item->product->sku }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-xs text-gray-500 mb-1">Jumlah diminta</p>
                        <p class="text-lg font-bold text-gray-800">{{ number_format($item->quantity, 3, ',', '.') }} <span class="text-sm text-gray-500">{{ $item->product->unit->symbol }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($purchaseRequest->status === 'approved')
        <p class="rounded-xl bg-green-50 p-4 text-sm text-green-800">PR disetujui otomatis saat dikirim dan siap diproses oleh Purchasing.</p>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        @if ($relatedOrder)
            <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                Lihat Purchase Order {{ $relatedOrder->reference }}
            </a>
        @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white hover:bg-[#b01030] transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Buat Purchase Order
            </a>
        @endif
    </div>
</section>
@endsection
