@extends('layouts.dashboard')

@php
    $pageTitle = 'Persetujuan — Technolife';
    $decisionStatuses = [
        'approved' => ['label' => 'Disetujui', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'icon' => 'task_alt'],
        'rejected' => ['label' => 'Ditolak', 'class' => 'border-rose-200 bg-rose-50 text-rose-800', 'icon' => 'cancel'],
    ];
    $pendingCount = (int) $stats['stock_in_pending'] + (int) $stats['transfer_pending'] + (int) $stats['opname_pending'];
    $resolvedCount = $resolvedDecisions->count();
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-10">
    <header class="rounded-2xl border border-slate-200 border-l-4 border-l-[#DC143C] bg-white p-4 shadow-2xs">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-[#DC143C]"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Kontrol operasional</span>
                <h1 class="mt-2 font-poppins text-xl font-semibold text-slate-900">Persetujuan</h1>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">Tinjau Stock In, distribusi gudang, dan hasil hitung stok.</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#FFF1F0] text-[#DC143C]"><span class="material-symbols-outlined">fact_check</span></span>
        </div>
    </header>

    @if(session('status'))
        <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800"><span class="material-symbols-outlined text-[18px]">check_circle</span><p>{{ session('status') }}</p></div>
    @endif

    <section class="grid grid-cols-2 gap-2.5" aria-label="Ringkasan persetujuan">
        <a href="#antrian-persetujuan" class="rounded-2xl border border-amber-200 bg-amber-50 p-3.5 transition hover:shadow-sm">
            <div class="flex items-center justify-between"><span class="text-[10px] font-semibold uppercase tracking-wide text-amber-800">Perlu ditinjau</span><span class="material-symbols-outlined text-[18px] text-amber-700">pending_actions</span></div>
            <p class="mt-1 font-poppins text-2xl font-semibold text-amber-900">{{ $pendingCount }}</p>
            <p class="mt-0.5 text-[10px] text-amber-800">Semua jenis approval</p>
        </a>
        <a href="#riwayat-keputusan" class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-2xs transition hover:border-primary/30">
            <div class="flex items-center justify-between"><span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Keputusan terbaru</span><span class="material-symbols-outlined text-[18px] text-slate-500">history</span></div>
            <p class="mt-1 font-poppins text-2xl font-semibold text-slate-900">{{ $resolvedCount }}</p>
            <p class="mt-0.5 text-[10px] text-slate-500">20 keputusan terbaru</p>
        </a>
    </section>

    @if($canReviewStockIns)
    <section class="space-y-3" aria-label="Antrian Stock In">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">PIC Outlet</p><h2 class="mt-0.5 text-sm font-semibold text-slate-900">Persetujuan Stock In</h2></div><span class="rounded-full {{ $pendingStockIns->isNotEmpty() ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[10px] font-semibold">{{ $pendingStockIns->count() }} menunggu</span></div>
        @forelse($pendingStockIns as $receipt)
            <a href="{{ route('inventory.stock-in.show', $receipt) }}" class="group block rounded-2xl border border-slate-200 border-l-4 border-l-rose-400 bg-white p-4 shadow-2xs transition hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-sm"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $receipt->reference }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $receipt->location->name }} · {{ $receipt->source }}</p></div><span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-800">Perlu ditinjau</span></div><div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-500"><span>{{ $receipt->items->count() }} produk · {{ $receipt->received_at?->format('d M Y') }}</span><span class="font-semibold text-[#DC143C]">Tinjau →</span></div></a>
        @empty <div class="rounded-xl border border-dashed border-slate-300 bg-white p-5 text-center text-xs text-slate-500">Tidak ada Stock In yang menunggu persetujuan.</div> @endforelse
    </section>
    @endif

    @if($canReviewTransfers)
    <section class="space-y-3" aria-label="Antrian transfer">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">PIC Gudang</p><h2 class="mt-0.5 text-sm font-semibold text-slate-900">Persetujuan Distribusi</h2></div><span class="rounded-full {{ $pendingTransfers->isNotEmpty() ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[10px] font-semibold">{{ $pendingTransfers->count() }} menunggu</span></div>
        @forelse($pendingTransfers as $transfer)
            <a href="{{ route('inventory.transfers.show', $transfer) }}" class="group block rounded-2xl border border-slate-200 border-l-4 border-l-amber-400 bg-white p-4 shadow-2xs transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $transfer->reference }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $transfer->sourceLocation->name }} → {{ $transfer->destinationLocation->name }}</p></div><span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-800">Perlu ditinjau</span></div><div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-500"><span>{{ $transfer->items->count() }} produk · {{ $transfer->created_at?->format('d M Y') }}</span><span class="font-semibold text-[#DC143C]">Tinjau →</span></div></a>
        @empty <div class="rounded-xl border border-dashed border-slate-300 bg-white p-5 text-center text-xs text-slate-500">Tidak ada distribusi yang menunggu persetujuan.</div> @endforelse
    </section>
    @endif

    @if($canReviewOpnames)
    <section id="antrian-persetujuan" class="scroll-mt-4 space-y-3" aria-label="Antrian Stock Opname">
        <div class="flex items-end justify-between gap-3">
            <div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Antrian kerja</p><h2 class="mt-0.5 text-sm font-semibold text-slate-900">Stock Opname</h2></div>
            <span class="rounded-full {{ $pendingCount > 0 ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[10px] font-semibold">{{ $pendingCount }} menunggu</span>
        </div>

        @forelse($pendingOpnames as $opname)
            <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="group block rounded-2xl border border-slate-200 border-l-4 border-l-amber-400 bg-white p-4 shadow-2xs transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><p class="truncate text-sm font-semibold text-slate-900">{{ $opname->reference }}</p>@if(str_starts_with($opname->reference, 'DEMO-SO-'))<span class="rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-violet-700">Data demo</span>@endif</div>
                        <p class="mt-1 flex items-center gap-1 text-[11px] text-slate-500"><span class="material-symbols-outlined text-[14px]">location_on</span>{{ $opname->location->name }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-800">Perlu ditinjau</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-[11px]">
                    <div><p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">Petugas</p><p class="mt-0.5 truncate font-medium text-slate-700">{{ $opname->counter->name }}</p></div>
                    <div><p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">Produk · dikirim</p><p class="mt-0.5 font-medium text-slate-700">{{ $opname->items->count() }} produk · {{ $opname->submitted_at?->format('d M, H:i') ?? '—' }}</p></div>
                </div>
                <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5"><span class="text-[10px] text-slate-500">Periksa selisih sebelum menyetujui</span><span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#DC143C]">Tinjau <span class="material-symbols-outlined text-[15px] transition-transform group-hover:translate-x-0.5">arrow_forward</span></span></div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><span class="material-symbols-outlined">task_alt</span></span>
                <p class="mt-3 text-sm font-semibold text-slate-800">Antrian sudah bersih</p>
                <p class="mt-1 text-xs text-slate-500">Tidak ada hasil Stock Opname yang menunggu persetujuan.</p>
            </div>
        @endforelse
    </section>
    @endif

    <section id="riwayat-keputusan" class="scroll-mt-4 space-y-3" aria-label="Riwayat keputusan">
        <div class="flex items-end justify-between gap-3"><div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Aktivitas terakhir</p><h2 class="mt-0.5 text-sm font-semibold text-slate-900">Riwayat keputusan</h2></div><span class="text-[10px] text-slate-500">{{ $resolvedCount }} catatan</span></div>
        @forelse($resolvedDecisions as $entry)
            @php($decision = $decisionStatuses[$entry['status']] ?? ['label' => $entry['status'], 'class' => 'border-slate-200 bg-slate-50 text-slate-700', 'icon' => 'help'])
            <a href="{{ $entry['url'] }}" class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs transition hover:border-slate-300">
                <div class="min-w-0"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-600">{{ $entry['type'] }}</span><p class="mt-1 truncate text-xs font-semibold text-slate-800">{{ $entry['reference'] }}</p><p class="mt-1 truncate text-[10px] text-slate-500">{{ $entry['location'] }}</p><p class="mt-0.5 truncate text-[10px] text-slate-400">{{ $entry['context'] }} · diputuskan {{ $entry['reviewer'] }}</p><p class="mt-0.5 text-[10px] text-slate-400">{{ $entry['date']?->format('d M Y, H:i') ?? '—' }}</p></div>
                <span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-1 text-[9px] font-semibold {{ $decision['class'] }}"><span class="material-symbols-outlined text-[13px]">{{ $decision['icon'] }}</span>{{ $decision['label'] }}</span>
            </a>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white p-4 text-center text-xs text-slate-500">Belum ada keputusan yang tercatat.</div>
        @endforelse
    </section>
    </main>
@endsection

@section('desktop-content')
<section class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4 rounded-3xl border border-slate-200 border-l-4 border-l-[#DC143C] bg-white px-6 py-5 shadow-sm">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#DC143C]"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Kontrol operasional</span>
            <h1 class="mt-2 font-poppins text-2xl font-semibold text-slate-900">Persetujuan</h1>
            <p class="mt-1 text-sm text-slate-500">Tinjau Stock In, distribusi gudang, dan hasil opname sesuai kewenangan akun.</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl {{ $pendingCount > 0 ? 'bg-amber-50 text-amber-900' : 'bg-emerald-50 text-emerald-900' }} px-4 py-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/80"><span class="material-symbols-outlined">{{ $pendingCount > 0 ? 'pending_actions' : 'task_alt' }}</span></span>
            <span><span class="block text-[10px] font-semibold uppercase tracking-wide opacity-70">Perlu ditinjau</span><span class="mt-0.5 block font-poppins text-xl font-semibold leading-none">{{ $pendingCount }}</span></span>
        </div>
    </header>

    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><span class="material-symbols-outlined text-emerald-700">check_circle</span>{{ session('status') }}</div>
    @endif

    @if($canReviewStockIns || $canReviewTransfers)
    <div class="grid gap-6 {{ $canReviewStockIns && $canReviewTransfers ? 'xl:grid-cols-2' : 'xl:grid-cols-1' }}">
        @if($canReviewStockIns)
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Antrian Stock In">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700"><span class="material-symbols-outlined">move_to_inbox</span></span><div><h2 class="text-sm font-semibold text-slate-900">Persetujuan Stock In</h2><p class="mt-0.5 text-[11px] text-slate-500">Antrian outlet yang ditugaskan</p></div></div><span class="rounded-full {{ $pendingStockIns->isNotEmpty() ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-3 py-1.5 text-xs font-semibold">{{ $pendingStockIns->count() }} menunggu</span></div>
            <div class="divide-y divide-slate-100">
                @forelse($pendingStockIns as $receipt)
                    <a href="{{ route('inventory.stock-in.show', $receipt) }}" class="group flex items-center justify-between gap-4 border-l-2 border-l-rose-400 px-5 py-4 transition hover:bg-rose-50/40"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ $receipt->reference }}</span><span class="mt-1 block truncate text-xs text-slate-500">{{ $receipt->location->name }} · {{ $receipt->source }}</span></span><span class="shrink-0 text-xs font-semibold text-[#DC143C]">Tinjau →</span></a>
                @empty <div class="px-5 py-10 text-center text-xs text-slate-500">Tidak ada Stock In yang menunggu persetujuan.</div> @endforelse
            </div>
        </section>
        @endif
        @if($canReviewTransfers)
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Antrian transfer">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><span class="material-symbols-outlined">local_shipping</span></span><div><h2 class="text-sm font-semibold text-slate-900">Persetujuan Distribusi</h2><p class="mt-0.5 text-[11px] text-slate-500">Transfer keluar dari gudang yang ditugaskan</p></div></div><span class="rounded-full {{ $pendingTransfers->isNotEmpty() ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-3 py-1.5 text-xs font-semibold">{{ $pendingTransfers->count() }} menunggu</span></div>
            <div class="divide-y divide-slate-100">
                @forelse($pendingTransfers as $transfer)
                    <a href="{{ route('inventory.transfers.show', $transfer) }}" class="group flex items-center justify-between gap-4 border-l-2 border-l-amber-400 px-5 py-4 transition hover:bg-amber-50/40"><span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">{{ $transfer->reference }}</span><span class="mt-1 block truncate text-xs text-slate-500">{{ $transfer->sourceLocation->name }} → {{ $transfer->destinationLocation->name }}</span></span><span class="shrink-0 text-xs font-semibold text-[#DC143C]">Tinjau →</span></a>
                @empty <div class="px-5 py-10 text-center text-xs text-slate-500">Tidak ada distribusi yang menunggu persetujuan.</div> @endforelse
            </div>
        </section>
        @endif
    </div>
    @endif

    @if($canReviewOpnames)
    <div class="grid gap-6">
        <section id="antrian-persetujuan" class="scroll-mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Antrian Stock Opname">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><span class="material-symbols-outlined">inventory_2</span></span><div><h2 class="text-sm font-semibold text-slate-900">Stock Opname</h2><p class="mt-0.5 text-[11px] text-slate-500">Hasil hitung menunggu tinjauan Purchasing</p></div></div>
                <span class="rounded-full {{ $pendingCount > 0 ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-600' }} px-3 py-1.5 text-xs font-semibold">{{ $pendingCount }} menunggu</span>
            </div>
            @if($pendingOpnames->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach($pendingOpnames as $opname)
                        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="group flex flex-wrap items-center justify-between gap-4 border-l-2 border-l-amber-400 px-5 py-4 transition hover:bg-amber-50/40">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><span class="material-symbols-outlined">fact_check</span></span>
                                <span class="min-w-0"><span class="flex flex-wrap items-center gap-2"><span class="truncate text-sm font-semibold text-slate-900">{{ $opname->reference }}</span>@if(str_starts_with($opname->reference, 'DEMO-SO-'))<span class="rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-violet-700">Data demo</span>@endif</span><span class="mt-1 block truncate text-xs text-slate-500">{{ $opname->location->name }} · petugas {{ $opname->counter->name }}</span></span>
                            </div>
                            <div class="flex items-center gap-5 pl-[52px] sm:pl-0"><span class="text-xs text-slate-500">{{ $opname->items->count() }} produk · {{ $opname->submitted_at?->format('d M Y, H:i') ?? '—' }}</span><span class="inline-flex items-center gap-1 rounded-lg bg-[#DC143C] px-3 py-2 text-xs font-semibold text-white">Tinjau<span class="material-symbols-outlined text-[15px] transition-transform group-hover:translate-x-0.5">arrow_forward</span></span></div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-12 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><span class="material-symbols-outlined text-[25px]">task_alt</span></span><p class="mt-3 text-sm font-semibold text-slate-800">Antrian sudah bersih</p><p class="mt-1 text-xs text-slate-500">Tidak ada hasil Stock Opname yang menunggu persetujuan.</p></div>
            @endif
        </section>


    </div>
    @endif
    <section id="riwayat-keputusan" class="scroll-mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Riwayat keputusan">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><span class="material-symbols-outlined">history</span></span><div><h2 class="text-sm font-semibold text-slate-900">Riwayat keputusan</h2><p class="mt-0.5 text-[11px] text-slate-500">Stock In, distribusi, dan Stock Opname</p></div></div><span class="text-xs text-slate-500">{{ $resolvedCount }} catatan</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($resolvedDecisions as $entry)
                @php($decision = $decisionStatuses[$entry['status']] ?? ['label' => $entry['status'], 'class' => 'border-slate-200 bg-slate-50 text-slate-700', 'icon' => 'help'])
                <a href="{{ $entry['url'] }}" class="block px-5 py-4 transition hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-600">{{ $entry['type'] }}</span><p class="mt-1 truncate text-xs font-semibold text-slate-800">{{ $entry['reference'] }}</p></div><span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $decision['class'] }}"><span class="material-symbols-outlined text-[14px]">{{ $decision['icon'] }}</span>{{ $decision['label'] }}</span></div>
                    <div class="mt-2 flex flex-wrap justify-between gap-x-4 gap-y-1 text-[10px] text-slate-500"><span>{{ $entry['location'] }}</span><span>{{ $entry['context'] }} · diputuskan {{ $entry['reviewer'] }}</span><span>{{ $entry['date']?->format('d M Y, H:i') ?? '—' }}</span></div>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-500">Belum ada keputusan yang tercatat.</div>
            @endforelse
        </div>
    </section>
</section>
@endsection
