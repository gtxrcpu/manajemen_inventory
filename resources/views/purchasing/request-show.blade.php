@extends('layouts.dashboard')

@php
    $pageTitle = 'Dokumen Purchase Request';
    $statusLabels = [
        'pending_approval' => 'Menunggu persetujuan',
        'approved' => $purchaseRequest->reviewer ? 'Disetujui Management' : 'Disetujui',
        'rejected' => 'Ditolak Management',
        'ordered' => 'PO sudah diterbitkan',
    ];
    $statusStyles = [
        'pending_approval' => 'border-amber-200 bg-amber-50 text-amber-800',
        'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'rejected' => 'border-rose-200 bg-rose-50 text-rose-800',
        'ordered' => 'border-blue-200 bg-blue-50 text-blue-800',
    ];
    $priorityLabels = ['normal' => 'Normal', 'urgent' => 'Darurat', 'emergency' => 'Darurat'];
    $priorityStyles = ['normal' => 'border-slate-200 bg-slate-100 text-slate-700', 'urgent' => 'border-rose-200 bg-rose-50 text-rose-800', 'emergency' => 'border-rose-200 bg-rose-50 text-rose-800'];
    $relatedOrder = $purchaseRequest->purchaseOrder->where('status', '!=', 'cancelled')->sortByDesc('id')->first();
    $itemCount = $purchaseRequest->items->count();
    $decisionUser = $purchaseRequest->status === 'approved'
        ? ($purchaseRequest->approver ?? $purchaseRequest->reviewer)
        : ($purchaseRequest->rejector ?? $purchaseRequest->reviewer);
@endphp

@section('mobile-content')
<main class="mx-auto w-full max-w-3xl space-y-4 p-4">
    <a href="{{ $canReviewPurchaseRequest ? route('approvals.index') : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-[#DC143C]">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>{{ $canReviewPurchaseRequest ? 'Daftar persetujuan' : 'Daftar pengadaan' }}
    </a>

    @if (session('status'))
        <div class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900"><span class="material-symbols-outlined text-[19px] text-emerald-700">check_circle</span><p>{{ session('status') }}</p></div>
    @endif

    @if ($purchaseRequest->status === 'pending_approval' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.requests.edit', $purchaseRequest) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm"><span class="material-symbols-outlined text-[18px]">edit</span>Ubah Purchase Request</a>
    @endif

    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="h-1 bg-[#DC143C]"></div>
        <header class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Technolife <span class="mx-1 text-slate-300">/</span> Dokumen pengadaan</p>
                    <h1 class="mt-3 font-poppins text-xl font-semibold tracking-tight text-slate-950">Permintaan Pembelian</h1>
                    <p class="mt-1 break-all font-mono text-sm font-semibold text-[#DC143C]">{{ $purchaseRequest->reference }}</p>
                </div>
                <span class="shrink-0 rounded-full border px-2.5 py-1 text-center text-[10px] font-semibold {{ $statusStyles[$purchaseRequest->status] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">{{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}</span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-slate-100 pt-4">
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Tanggal diajukan</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y') }}</p><p class="text-xs text-slate-500">{{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('H:i') }} WIB</p></div>
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pemohon</p><p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $purchaseRequest->requester?->name ?? 'Pengguna' }}</p><p class="text-xs text-slate-500">{{ $purchaseRequest->requester?->email ?? '—' }}</p></div>
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Lokasi tujuan</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $purchaseRequest->location?->name ?? 'Belum ditentukan' }}</p><p class="text-xs text-slate-500">{{ $purchaseRequest->location?->type === 'warehouse' ? 'Gudang' : ($purchaseRequest->location ? 'Outlet' : 'PR lama') }}</p></div>
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Tanggal dibutuhkan</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ $purchaseRequest->required_date?->format('d M Y') ?? 'Belum ditentukan' }}</p></div>
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Prioritas</p><span class="mt-1 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $priorityStyles[$purchaseRequest->priority ?? 'normal'] ?? $priorityStyles['normal'] }}">{{ $priorityLabels[$purchaseRequest->priority ?? 'normal'] ?? 'Normal' }}</span></div>
                <div><p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Rekomendasi supplier</p><p class="mt-1 line-clamp-2 text-sm font-semibold text-slate-800">{{ $purchaseRequest->recommendedSupplier?->name ?? 'Belum ditentukan' }}</p></div>
            </div>
        </header>

        <section class="border-t border-slate-200 bg-[#FCFCFD] px-5 py-4" aria-label="Alasan pembelian"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Alasan / justifikasi pembelian</p><p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $purchaseRequest->justification ?: 'Alasan tidak dicatat pada PR lama.' }}</p></section>

        <section class="border-t border-slate-200" aria-labelledby="mobile-request-items-title">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Rincian permintaan</p><h2 id="mobile-request-items-title" class="mt-1 text-sm font-semibold text-slate-900">Barang yang diminta</h2></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $itemCount }} item</span></div>
            <div class="divide-y divide-slate-100">
                @foreach ($purchaseRequest->items as $index => $item)
                    <div class="space-y-3 px-5 py-4">
                        <div class="flex items-start gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 font-mono text-xs font-semibold text-slate-500">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-slate-900">{{ $item->product->name }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $item->product->sku }}</p>@if($item->notes)<p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $item->notes }}</p>@endif</div></div>
                        <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs"><div><p class="text-slate-400">Stok saat diajukan</p><p class="mt-1 font-semibold text-slate-800">{{ $item->stock_snapshot !== null ? \App\Support\QuantityFormatter::format($item->stock_snapshot).' '.$item->product->unit->symbol : '—' }}</p>@if(isset($minimumStocks[$item->product_id]) && $item->stock_snapshot !== null && (float)$item->stock_snapshot < (float)$minimumStocks[$item->product_id])<span class="mt-1 inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Di bawah minimum · {{ \App\Support\QuantityFormatter::format($minimumStocks[$item->product_id]) }}</span>@endif</div><div><p class="text-slate-400">Jumlah diminta</p><p class="mt-1 font-semibold text-slate-900">{{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}</p></div><div><p class="text-slate-400">Estimasi harga satuan</p><p class="mt-1 font-semibold text-slate-800">{{ $item->estimated_unit_price !== null ? 'Rp'.number_format((float)$item->estimated_unit_price, 2, ',', '.') : 'Belum tersedia' }}</p></div><div><p class="text-slate-400">Estimasi subtotal</p><p class="mt-1 font-semibold text-slate-900">{{ $item->estimated_subtotal !== null ? 'Rp'.number_format((float)$item->estimated_subtotal, 2, ',', '.') : 'Belum tersedia' }}</p></div></div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4"><span class="text-sm font-semibold text-slate-700">Total estimasi anggaran</span><strong class="text-sm font-bold tabular-nums text-slate-950">{{ $purchaseRequest->estimated_total !== null ? 'Rp'.number_format((float)$purchaseRequest->estimated_total, 2, ',', '.') : 'Belum tersedia' }}</strong></div>
        </section>

        @if ($purchaseRequest->notes)
            <section class="space-y-3 border-t border-slate-200 p-5">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Catatan pemohon</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $purchaseRequest->notes }}</p></div>
            </section>
        @endif
    </article>

    @if($purchaseRequest->reviewed_at)
        <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Riwayat keputusan Management"><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Riwayat persetujuan</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $purchaseRequest->status === 'rejected' ? 'Ditolak' : 'Disetujui' }}{{ $decisionUser ? ' oleh '.$decisionUser->name : '' }}</p><p class="mt-1 text-xs text-slate-500">{{ $purchaseRequest->reviewed_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p><p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $purchaseRequest->review_note ?: 'Tidak ada catatan review.' }}</p></section>
    @endif

    @include('purchasing._request-review-actions')

    @if ($purchaseRequest->status === 'pending_approval' && ! $canReviewPurchaseRequest)
        <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><span class="material-symbols-outlined text-amber-700">schedule</span><p><strong>Menunggu keputusan Management.</strong><br>Purchase Order dapat dibuat setelah permintaan disetujui.</p></div>
    @elseif ($purchaseRequest->status === 'approved')
        <div class="flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><span class="material-symbols-outlined text-emerald-700">task_alt</span><p><strong>Permintaan disetujui.</strong><br>{{ $purchaseRequest->reviewer ? 'Disetujui oleh '.$purchaseRequest->reviewer->name.'. ' : '' }}Purchasing dapat melanjutkan ke pembuatan PO.</p></div>
    @elseif ($purchaseRequest->status === 'rejected')
        <div class="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><span class="material-symbols-outlined text-rose-700">block</span><p><strong>Permintaan ditolak Management.</strong><br>Silakan lihat catatan keputusan pada dokumen ini.</p></div>
    @endif

    @if ($relatedOrder && (auth()->user()->hasPermissionTo('purchasing.manage') || auth()->user()->hasPermissionTo('stock.manage')))
        <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"><span class="material-symbols-outlined text-[18px]">description</span>Buka PO {{ $relatedOrder->reference }}</a>
    @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b01030]"><span class="material-symbols-outlined text-[18px]">add</span>Buat Purchase Order</a>
    @endif
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-5">
    <a href="{{ $canReviewPurchaseRequest ? route('approvals.index') : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-[#DC143C]"><span class="material-symbols-outlined text-[18px]">arrow_back</span>{{ $canReviewPurchaseRequest ? 'Daftar persetujuan' : 'Daftar pengadaan' }}</a>

    @if (session('status'))
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><span class="material-symbols-outlined text-emerald-700">check_circle</span><p>{{ session('status') }}</p></div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="h-1.5 bg-[#DC143C]"></div>
        <header class="grid gap-8 p-8 lg:grid-cols-[1fr_auto] lg:items-start">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Technolife <span class="mx-1 text-slate-300">/</span> Dokumen pengadaan</p>
                <p class="mt-7 text-xs font-semibold uppercase tracking-[0.14em] text-[#DC143C]">Purchase Request</p>
                <h1 class="mt-2 font-poppins text-3xl font-semibold tracking-tight text-slate-950">Permintaan Pembelian</h1>
                <p class="mt-2 font-mono text-lg font-semibold tracking-wide text-slate-600">{{ $purchaseRequest->reference }}</p>
                <p class="mt-4 text-sm text-slate-500">Diajukan {{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y') }} pukul {{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('H:i') }} WIB</p>
            </div>
            <div class="flex flex-col items-start gap-3 lg:items-end">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Status dokumen</p>
                <span class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $statusStyles[$purchaseRequest->status] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}"><span class="h-2 w-2 rounded-full bg-current"></span>{{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}</span>
                <p class="max-w-xs text-right text-xs leading-relaxed text-slate-500">{{ $purchaseRequest->status === 'pending_approval' ? 'Menunggu pemeriksaan dan keputusan Management.' : ($purchaseRequest->status === 'rejected' ? 'Permintaan tidak dapat dilanjutkan menjadi PO.' : ($relatedOrder ? 'Purchase Order telah diterbitkan.' : 'Siap diproses oleh Purchasing.')) }}</p>
            </div>
        </header>

        <section class="grid border-y border-slate-200 bg-slate-50 sm:grid-cols-2 xl:grid-cols-3" aria-label="Informasi dokumen">
            <div class="border-b border-slate-200 px-8 py-5 sm:border-b-0 sm:border-r"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Pemohon</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $purchaseRequest->requester?->name ?? 'Pengguna' }}</p><p class="mt-1 text-xs text-slate-500">{{ $purchaseRequest->requester?->email ?? '—' }}</p></div>
            <div class="border-b border-slate-200 px-8 py-5 sm:border-b-0 xl:border-r"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Lokasi tujuan</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $purchaseRequest->location?->name ?? 'Belum ditentukan · PR lama' }}</p><p class="mt-1 text-xs text-slate-500">{{ $purchaseRequest->location?->type === 'warehouse' ? 'Gudang penerimaan' : ($purchaseRequest->location ? 'Outlet penerima' : 'Lokasi tidak tercatat') }}</p></div>
            <div class="border-b border-slate-200 px-8 py-5 xl:border-r"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Prioritas</p><span class="mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $priorityStyles[$purchaseRequest->priority ?? 'normal'] ?? $priorityStyles['normal'] }}">{{ $priorityLabels[$purchaseRequest->priority ?? 'normal'] ?? 'Normal' }}</span><p class="mt-1 text-xs text-slate-500">{{ in_array($purchaseRequest->priority, ['emergency', 'urgent'], true) ? 'Ditampilkan di atas PR Normal pada antrean review Management.' : 'Diproses sesuai urutan pengajuan setelah PR Darurat.' }}</p></div>
            <div class="border-b border-slate-200 px-8 py-5 xl:border-r"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Rekomendasi supplier</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $purchaseRequest->recommendedSupplier?->name ?? 'Belum ditentukan' }}</p><p class="mt-1 text-xs text-slate-500">Referensi pemohon</p></div>
            <div class="border-b border-slate-200 px-8 py-5 sm:border-b-0 xl:border-r"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Tanggal permintaan</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('d M Y') }}</p><p class="mt-1 text-xs text-slate-500">{{ $purchaseRequest->created_at->copy()->timezone('Asia/Jakarta')->format('H:i') }} WIB</p></div>
            <div class="px-8 py-5"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Tanggal dibutuhkan</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ $purchaseRequest->required_date?->format('d M Y') ?? 'Belum ditentukan' }}</p><p class="mt-1 text-xs text-slate-500">{{ $itemCount }} jenis barang diminta</p></div>
        </section>

        <section class="border-b border-slate-200 bg-[#FCFCFD] px-8 py-6" aria-label="Alasan pembelian"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Alasan / justifikasi pembelian</p><p class="mt-2 max-w-4xl whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $purchaseRequest->justification ?: 'Alasan tidak dicatat pada PR lama.' }}</p></section>

        <section aria-labelledby="request-items-title">
            <div class="flex items-end justify-between gap-4 px-8 pb-4 pt-7"><div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Rincian barang</p><h2 id="request-items-title" class="mt-1 text-lg font-semibold text-slate-900">Daftar permintaan</h2></div><span class="text-xs font-medium text-slate-500">{{ $itemCount }} baris</span></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] border-collapse text-left">
                    <thead><tr class="border-y border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-500"><th class="w-16 px-6 py-3">No.</th><th class="px-4 py-3">Barang</th><th class="w-44 px-4 py-3 text-right">Stok saat ini</th><th class="w-40 px-4 py-3 text-right">Jumlah diminta</th><th class="w-44 px-4 py-3 text-right">Est. harga satuan</th><th class="w-44 px-8 py-3 text-right">Est. subtotal</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($purchaseRequest->items as $index => $item)
                            <tr class="align-top"><td class="px-6 py-4 font-mono text-xs text-slate-400">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td><td class="px-4 py-4"><p class="text-sm font-semibold text-slate-900">{{ $item->product->name }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $item->product->sku }}</p>@if($item->notes)<p class="mt-2 max-w-md text-xs leading-relaxed text-slate-600">{{ $item->notes }}</p>@endif</td><td class="px-4 py-4 text-right"><p class="text-sm font-semibold tabular-nums text-slate-800">{{ $item->stock_snapshot !== null ? \App\Support\QuantityFormatter::format($item->stock_snapshot).' '.$item->product->unit->symbol : '—' }}</p>@if(isset($minimumStocks[$item->product_id]) && $item->stock_snapshot !== null && (float)$item->stock_snapshot < (float)$minimumStocks[$item->product_id])<span class="mt-1 inline-flex rounded-full bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700">Di bawah minimum · {{ \App\Support\QuantityFormatter::format($minimumStocks[$item->product_id]) }}</span>@endif</td><td class="px-4 py-4 text-right text-sm font-semibold tabular-nums text-slate-900">{{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $item->product->unit->symbol }}</td><td class="px-4 py-4 text-right text-sm tabular-nums text-slate-700">{{ $item->estimated_unit_price !== null ? 'Rp'.number_format((float)$item->estimated_unit_price, 2, ',', '.') : 'Belum tersedia' }}</td><td class="px-8 py-4 text-right text-sm font-semibold tabular-nums text-slate-900">{{ $item->estimated_subtotal !== null ? 'Rp'.number_format((float)$item->estimated_subtotal, 2, ',', '.') : 'Belum tersedia' }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="border-t-2 border-slate-200 bg-slate-50"><td colspan="5" class="px-6 py-4 text-right text-sm font-semibold text-slate-700">Total estimasi anggaran</td><td class="px-8 py-4 text-right text-base font-bold tabular-nums text-slate-950">{{ $purchaseRequest->estimated_total !== null ? 'Rp'.number_format((float)$purchaseRequest->estimated_total, 2, ',', '.') : 'Belum tersedia' }}</td></tr></tfoot>
                </table>
            </div>
        </section>

        @if ($purchaseRequest->notes)
            <section class="border-t border-slate-200 bg-white px-8 py-6"><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Catatan pemohon</p><p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $purchaseRequest->notes }}</p></section>
        @endif
    </article>

    @if($purchaseRequest->reviewed_at)
        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 md:grid-cols-[1fr_auto]" aria-label="Riwayat keputusan Management"><div><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Riwayat persetujuan</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $purchaseRequest->status === 'rejected' ? 'Ditolak' : 'Disetujui' }}{{ $decisionUser ? ' oleh '.$decisionUser->name : '' }}</p><p class="mt-1 text-xs text-slate-500">{{ $purchaseRequest->reviewed_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p></div><p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 md:max-w-xl">{{ $purchaseRequest->review_note ?: 'Tidak ada catatan review.' }}</p></section>
    @endif

    @include('purchasing._request-review-actions')

    @if ($purchaseRequest->status === 'pending_approval' && ! $canReviewPurchaseRequest)
        <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><span class="material-symbols-outlined text-amber-700">schedule</span><p><strong>Menunggu keputusan Management.</strong><br>Purchase Order dapat dibuat setelah permintaan disetujui.</p></div>
    @elseif ($purchaseRequest->status === 'approved')
        <div class="flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><span class="material-symbols-outlined text-emerald-700">task_alt</span><p><strong>Permintaan disetujui.</strong><br>{{ $purchaseRequest->reviewer ? 'Disetujui oleh '.$purchaseRequest->reviewer->name.'. ' : '' }}Purchasing dapat melanjutkan ke pembuatan PO.</p></div>
    @elseif ($purchaseRequest->status === 'rejected')
        <div class="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><span class="material-symbols-outlined text-rose-700">block</span><p><strong>Permintaan ditolak Management.</strong><br>Silakan lihat catatan keputusan pada dokumen ini.</p></div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        @if ($purchaseRequest->status === 'pending_approval' && auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.requests.edit', $purchaseRequest) }}" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700"><span class="material-symbols-outlined text-[18px]">edit</span>Ubah PR</a>
        @endif
        @if ($relatedOrder && (auth()->user()->hasPermissionTo('purchasing.manage') || auth()->user()->hasPermissionTo('stock.manage')))
            <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"><span class="material-symbols-outlined text-[18px]">description</span>Buka PO {{ $relatedOrder->reference }}</a>
        @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b01030]"><span class="material-symbols-outlined text-[18px]">add</span>Buat Purchase Order</a>
        @endif
    </div>
</section>
@endsection
