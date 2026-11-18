@extends('layouts.dashboard')
@php
    $pageTitle = 'Faktur Pembelian &mdash; Technolife';
    $statusLabels = [
        'draft'             => 'Draft',
        'submitted'         => 'Menunggu Review',
        'accepted'          => 'Disetujui Finance',
        'revision_required' => 'Perlu Revisi',
        'rejected'          => 'Ditolak',
    ];
    $statusClasses = [
        'draft'             => 'bg-slate-100 text-slate-700 border border-slate-200',
        'submitted'         => 'bg-amber-50 text-amber-800 border border-amber-200/80',
        'accepted'          => 'bg-emerald-50 text-emerald-800 border border-emerald-200/80',
        'revision_required' => 'bg-orange-50 text-orange-800 border border-orange-200/80',
        'rejected'          => 'bg-rose-50 text-rose-800 border border-rose-200/80',
    ];

    $totalCount = \App\Models\PurchaseInvoice::count();
    $submittedCount = \App\Models\PurchaseInvoice::where('status', 'submitted')->count();
    $acceptedCount = \App\Models\PurchaseInvoice::where('status', 'accepted')->count();
    $totalAmount = (float) \App\Models\PurchaseInvoice::where('status', 'accepted')->sum('document_total');
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <div class="space-y-3">
        <a href="{{ route('purchasing.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary">&larr; Kembali ke Pengadaan</a>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="font-poppins text-xl font-bold text-gray-900">Faktur Pembelian</h1>
                <p class="mt-0.5 text-xs text-gray-500">Pemeriksaan dokumen tagihan &amp; validasi Finance.</p>
            </div>
            <div class="flex items-center gap-1.5">
                @if(auth()->user()->hasPermissionTo('invoice.manage'))
                    <a class="inline-flex items-center gap-1 rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white shadow-sm" href="{{ route('purchase-invoices.create') }}">
                        <span class="material-symbols-outlined text-[16px]">add</span>Faktur
                    </a>
                @endif
                @if(auth()->user()->hasPermissionTo('invoice.review'))
                    <a class="rounded-xl border border-gray-200 bg-white px-2.5 py-2 text-xs font-semibold text-gray-700" href="{{ route('finance.valuation') }}">Valuasi</a>
                @endif
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- Stats Cards Mobile --}}
    <div class="grid grid-cols-2 gap-2.5">
        <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-gray-400">
                <span class="text-[10px] font-semibold uppercase tracking-wider">Total</span>
                <span class="material-symbols-outlined text-[16px] text-gray-500">receipt_long</span>
            </div>
            <p class="mt-1 font-poppins text-lg font-bold text-gray-900">{{ number_format($totalCount, 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-500">{{ $acceptedCount }} disetujui</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between text-amber-500">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Review</span>
                <span class="material-symbols-outlined text-[16px]">pending_actions</span>
            </div>
            <p class="mt-1 font-poppins text-lg font-bold text-amber-700">{{ number_format($submittedCount, 0, ',', '.') }}</p>
            <p class="text-[10px] text-amber-700 font-medium">Perlu review</p>
        </div>
    </div>

    {{-- Filter Bar Mobile --}}
    <form method="GET" class="space-y-2 rounded-2xl border border-gray-200 bg-white p-3.5 shadow-2xs">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[17px] text-gray-400 pointer-events-none">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Cari nomor faktur atau supplier..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-primary outline-none transition">
        </div>
        <div class="flex gap-2">
            <select name="status" class="flex-1 px-3 py-2 text-xs rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-primary outline-none transition">
                <option value="">Semua Status</option>
                @foreach($statusLabels as $statusKey => $label)
                    <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold text-white">Filter</button>
            @if(request()->hasAny(['q', 'status']))
                <a href="{{ route('purchase-invoices.index') }}" class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600">Reset</a>
            @endif
        </div>
    </form>

    {{-- Mobile Invoices List --}}
    <div class="space-y-3">
        @forelse($invoices as $invoice)
            @php
                $statusBadge = $statusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
                $statusName = $statusLabels[$invoice->status] ?? str_replace('_', ' ', ucfirst($invoice->status));
            @endphp
            <a class="block rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs active:bg-gray-50 transition" href="{{ route('purchase-invoices.show', $invoice) }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <span class="inline-block rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-mono font-semibold text-gray-700">{{ $invoice->invoice_number }}</span>
                        <h3 class="mt-1 font-semibold text-sm text-gray-900 truncate">{{ $invoice->supplier->name }}</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $invoice->invoice_date->format('d M Y') }} &middot; {{ $invoice->items_count }} item</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold shrink-0 {{ $statusBadge }}">{{ $statusName }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2.5">
                    @if($invoice->order)
                        <span class="text-[11px] text-primary font-medium truncate max-w-[140px]">PO: {{ $invoice->order->reference }}</span>
                    @else
                        <span class="text-[11px] text-gray-400">Non-PO</span>
                    @endif
                    <strong class="text-sm font-bold text-gray-900">Rp {{ number_format((float) $invoice->document_total, 2, ',', '.') }}</strong>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-xs text-gray-500 shadow-2xs">
                <span class="material-symbols-outlined text-[28px] text-gray-400">receipt_long</span>
                <p class="mt-2 font-semibold text-gray-700">Belum ada faktur pembelian.</p>
            </div>
        @endforelse
    </div>

    {{ $invoices->links() }}
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-7xl space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('purchasing.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#DC143C] hover:underline mb-1">&larr; Kembali ke Pengadaan</a>
            <h1 class="font-poppins text-2xl font-bold text-gray-900 tracking-tight">Faktur Pembelian</h1>
            <p class="mt-0.5 text-sm text-gray-500">Pemeriksaan dokumen tagihan supplier &amp; rekonsiliasi keputusan Finance.</p>
        </div>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasPermissionTo('invoice.review'))
                <a href="{{ route('finance.valuation') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-2xs transition">
                    <span class="material-symbols-outlined text-[18px] text-gray-500">monitoring</span>
                    Valuasi Persediaan
                </a>
            @endif
            @if(auth()->user()->hasPermissionTo('invoice.manage'))
                <a href="{{ route('purchase-invoices.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#DC143C] to-[#B1002C] px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-red-900/15 hover:from-[#c81036] hover:to-[#9e0026] active:scale-[0.98] transition">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Catat Faktur Baru
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-2xs">{{ session('status') }}</div>
    @endif

    {{-- KPI Metric Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-gray-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Faktur</span>
                <span class="material-symbols-outlined text-[20px] text-gray-600">receipt_long</span>
            </div>
            <p class="mt-2 font-poppins text-2xl font-bold text-gray-900">{{ number_format($totalCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-gray-500">Seluruh dokumen tercatat</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-amber-600">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Menunggu Review</span>
                <span class="material-symbols-outlined text-[20px]">pending_actions</span>
            </div>
            <p class="mt-2 font-poppins text-2xl font-bold text-amber-700">{{ number_format($submittedCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-amber-800/80 font-medium">Perlu review Finance</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-emerald-600">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Disetujui</span>
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
            </div>
            <p class="mt-2 font-poppins text-2xl font-bold text-emerald-700">{{ number_format($acceptedCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-emerald-800/80 font-medium">Faktur sah terverifikasi</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between text-rose-600">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Disetujui</span>
                <span class="material-symbols-outlined text-[20px] text-[#DC143C]">payments</span>
            </div>
            <p class="mt-2 font-poppins text-xl font-bold text-gray-900 truncate">Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-gray-500">Nilai invoice disetujui</p>
        </div>
    </div>

    {{-- Search & Filter Section --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 pointer-events-none">search</span>
            <input name="q" value="{{ request('q') }}" placeholder="Cari nomor faktur atau nama supplier..." class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-gray-200 bg-gray-50/50 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition">
        </div>
        <div class="relative min-w-[200px]">
            <select name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition appearance-none pr-8">
                <option value="">Semua Status</option>
                @foreach($statusLabels as $statusKey => $label)
                    <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>{{ $label }}</option>
                @endforeach
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-gray-400 pointer-events-none">expand_more</span>
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-800 active:scale-95 transition">
            <span class="material-symbols-outlined text-[16px]">filter_list</span>
            Filter
        </button>
        @if(request()->hasAny(['q', 'status']))
            <a href="{{ route('purchase-invoices.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-95 transition">
                Reset
            </a>
        @endif
    </form>

    {{-- Invoices Grid --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($invoices as $invoice)
            @php
                $statusBadge = $statusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
                $statusName = $statusLabels[$invoice->status] ?? str_replace('_', ' ', ucfirst($invoice->status));
            @endphp
            <a href="{{ route('purchase-invoices.show', $invoice) }}" class="group flex flex-col justify-between rounded-2xl border border-gray-200/90 bg-white p-5 shadow-2xs hover:border-[#DC143C]/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono font-semibold text-gray-700 group-hover:bg-red-50 group-hover:text-primary transition-colors">
                                {{ $invoice->invoice_number }}
                            </span>
                            <h3 class="mt-1.5 font-semibold text-gray-900 text-base truncate group-hover:text-[#DC143C] transition-colors">
                                {{ $invoice->supplier->name }}
                            </h3>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusBadge }}">
                            {{ $statusName }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-1.5 border-t border-gray-100 pt-3 text-xs text-gray-500">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-gray-400">
                                <span class="material-symbols-outlined text-[15px]">calendar_today</span>
                                Tanggal Faktur
                            </span>
                            <span class="font-medium text-gray-700">{{ $invoice->invoice_date->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-gray-400">
                                <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                                Jumlah Item
                            </span>
                            <span class="font-medium text-gray-700">{{ $invoice->items_count }} item</span>
                        </div>
                        @if($invoice->order)
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-gray-400">
                                    <span class="material-symbols-outlined text-[15px]">shopping_bag</span>
                                    Acuan PO
                                </span>
                                <span class="font-semibold text-primary truncate max-w-[140px]">{{ $invoice->order->reference }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-3.5">
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Total Dokumen</span>
                            <p class="font-poppins text-lg font-bold text-gray-900 group-hover:text-[#DC143C] transition-colors">
                                Rp {{ number_format((float) $invoice->document_total, 2, ',', '.') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-400 group-hover:text-[#DC143C] transition-colors">
                            Lihat <span class="material-symbols-outlined text-[16px] group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-400 truncate">
                        Dicatat oleh {{ $invoice->creator->name }}
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-2xs">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                    <span class="material-symbols-outlined text-[28px]">receipt_long</span>
                </div>
                <h3 class="mt-3 font-semibold text-gray-900 text-base">Belum Ada Faktur Pembelian</h3>
                <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">
                    @if(request()->hasAny(['q', 'status']))
                        Tidak ada faktur yang cocok dengan filter pencarian saat ini. Coba sesuaikan kata kunci atau reset filter.
                    @else
                        Faktur pembelian dari supplier yang dicatat atau ditautkan ke Purchase Order akan muncul di sini.
                    @endif
                </p>
                @if(request()->hasAny(['q', 'status']))
                    <a href="{{ route('purchase-invoices.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Reset Filter
                    </a>
                @elseif(auth()->user()->hasPermissionTo('invoice.manage'))
                    <a href="{{ route('purchase-invoices.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[#DC143C] px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Catat Faktur Baru
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{ $invoices->links() }}
</section>
@endsection
