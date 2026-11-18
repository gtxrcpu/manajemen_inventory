@extends('layouts.dashboard')

@php
    $isWarehouseStaff = auth()->user()->hasRole('staff_gudang');
    $pageTitle = $isWarehouseStaff ? 'Validasi Penerimaan Gudang' : 'Detail Purchase Order';
    $hasReceipts = $order->stockIn->isNotEmpty();
    $orderStatusLabel = $order->status === 'closed_with_variance'
        ? 'Ditutup dengan selisih'
        : ($order->status === 'received' ? 'Diterima lengkap' : ($hasReceipts ? 'Penerimaan sebagian' : 'Menunggu barang'));
    $orderStatusClass = $order->status === 'received'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
        : ($order->status === 'closed_with_variance'
            ? 'border-violet-200 bg-violet-50 text-violet-800'
            : ($hasReceipts ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-slate-50 text-slate-700'));
    $hasReceiptVariance = $order->stockIn->flatMap(fn ($receipt) => $receipt->items)->contains(fn ($line) => (float) ($line->delivered_quantity ?? $line->quantity) > (float) $line->quantity || filled($line->variance_note));
    $canManageSupplierFollowUp = auth()->user()->hasPermissionTo('purchasing.manage');
    $remainingPoQuantity = $order->items->sum(fn ($item) => max(0, (float) $item->quantity - (float) ($receivedQuantities[$item->id] ?? 0)));
    $canReceiveOrder = $order->status === 'issued' && auth()->user()->hasPermissionTo('stock.manage');
    $canCreateOrderInvoice = in_array($order->status, ['received', 'closed_with_variance'], true) && ! $invoice && auth()->user()->hasPermissionTo('invoice.manage');
    $arrivalStageComplete = $hasReceipts || in_array($order->status, ['received', 'closed_with_variance'], true);
@endphp

@section('mobile-content')
<main class="w-full space-y-5 p-4 pb-8">
    <a href="{{ $isWarehouseStaff ? route('inventory.stock-in.purchase-orders') : route('purchasing.index') }}" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-[#DC143C]"><span aria-hidden="true">←</span> {{ $isWarehouseStaff ? 'Daftar penerimaan' : 'Pengadaan' }}</a>

    @if (session('status'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @if (session('receipt_url'))
            <a href="{{ session('receipt_url') }}" class="-mt-2 block text-sm font-semibold text-primary">Buka bukti Stock In {{ session('receipt_reference') }} &rarr;</a>
        @endif
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
            <p class="font-semibold">Penerimaan belum tersimpan.</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="absolute inset-y-0 left-0 w-1 bg-[#DC143C]"></div>
        <div class="flex items-start justify-between gap-3 pl-2">
            <div class="min-w-0"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">{{ $isWarehouseStaff ? 'Penerimaan gudang' : 'Purchase Order' }}</p><h1 class="mt-1 break-words font-poppins text-xl font-semibold tracking-tight text-slate-950">{{ $order->reference }}</h1><p class="mt-1 text-xs text-slate-500">{{ $order->supplier->name }} <span class="px-1 text-slate-300">/</span> {{ $order->location->name }}</p></div>
            <span class="shrink-0 rounded-full border px-2.5 py-1.5 text-[10px] font-bold {{ $orderStatusClass }}">{{ $orderStatusLabel }}</span>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-4 border-t border-slate-100 pt-4 text-xs">
            <div><dt class="text-slate-400">Estimasi tiba</dt><dd class="mt-1 font-semibold text-slate-800">{{ $order->expected_delivery?->format('d M Y') ?? 'Belum ditentukan' }}</dd></div>
            <div><dt class="text-slate-400">Dibuat oleh</dt><dd class="mt-1 font-semibold text-slate-800">{{ $order->creator->name }}</dd></div>
        </dl>
        @unless($isWarehouseStaff)<p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">Permintaan asal <a href="{{ route('purchasing.requests.show', $order->request) }}" class="ml-1 font-semibold text-[#DC143C]">{{ $order->request->reference }} <span aria-hidden="true">↗</span></a></p>@endunless
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm" aria-label="Tahapan Purchase Order">
        <div class="grid grid-cols-3 gap-2 text-center">
            <div><span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><span class="material-symbols-outlined text-[17px]">check</span></span><p class="mt-1.5 text-[10px] font-semibold text-slate-700">PO diterbitkan</p></div>
            <div><span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full {{ $arrivalStageComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}"><span class="material-symbols-outlined text-[17px]">{{ $arrivalStageComplete ? 'check' : 'local_shipping' }}</span></span><p class="mt-1.5 text-[10px] font-semibold {{ $arrivalStageComplete ? 'text-slate-700' : 'text-slate-400' }}">Penerimaan</p></div>
            <div><span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full {{ $invoice ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }}"><span class="material-symbols-outlined text-[17px]">{{ $invoice ? 'check' : 'receipt_long' }}</span></span><p class="mt-1.5 text-[10px] font-semibold {{ $invoice ? 'text-slate-700' : 'text-slate-400' }}">Faktur</p></div>
        </div>
        <div class="mt-3 h-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $invoice ? '100%' : ($arrivalStageComplete ? '66%' : '33%') }}"></div></div>
    </section>

    @if ($order->status === 'issued')
        <section class="rounded-2xl border {{ $canReceiveOrder ? 'border-rose-200 bg-rose-50/80' : 'border-amber-200 bg-amber-50/80' }} p-4 shadow-sm" aria-label="Langkah berikutnya">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] {{ $canReceiveOrder ? 'text-[#DC143C]' : 'text-amber-800' }}">Tindakan berikutnya</p>
            <h2 class="mt-1 font-semibold">{{ $canReceiveOrder ? 'Periksa dan catat barang yang datang' : ($hasReceipts ? 'Tindak lanjuti sisa / selisih dengan supplier' : 'Menunggu penerimaan oleh Gudang') }}</h2>
            <p class="mt-1 text-sm {{ $canReceiveOrder ? 'text-gray-700' : 'text-amber-900' }}">{{ $canReceiveOrder ? 'Catat jumlah datang dan jumlah layak secara terpisah. Hanya jumlah layak yang menambah stok.' : ($hasReceipts ? 'Gudang sudah mencatat penerimaan sebagian. Purchasing perlu menghubungi supplier, mencatat janji kirim/pengganti atau penyelesaian klaim di bawah. PO tetap terbuka sampai sisa diterima atau ditutup dengan alasan.' : 'Staf Gudang melanjutkan pemeriksaan fisik dari PO ini. Jika jumlah atau kondisi berbeda, catat sebagai selisih agar Purchasing dapat menindaklanjuti supplier.') }}</p>
            @if ($canReceiveOrder)
                <a href="#receipt-form-mobile" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-4 py-3 text-sm font-bold text-white shadow-sm">Lanjut catat penerimaan <span aria-hidden="true">↓</span></a>
            @endif
        </section>
    @elseif ($canCreateOrderInvoice)
        <section class="rounded-2xl border-2 border-[#DC143C] bg-rose-50 p-4" aria-label="Langkah berikutnya">
            <p class="text-xs font-bold uppercase tracking-wide text-[#DC143C]">Langkah berikutnya</p>
            <h2 class="mt-1 font-semibold">Barang sudah diterima. Tautkan faktur</h2>
            <p class="mt-1 text-sm text-gray-700">Buat faktur dari PO ini agar dokumen dan nilai pembelian tercatat.</p>
            <a href="{{ route('purchase-invoices.create', ['purchase_order_id' => $order->id]) }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-[#DC143C] px-4 py-3 text-sm font-bold text-white shadow-sm">Buat faktur dari PO ini <span class="ml-2" aria-hidden="true">→</span></a>
        </section>
    @endif

    @if(!$isWarehouseStaff && $hasReceipts && ($remainingPoQuantity > 0 || $hasReceiptVariance) && $canManageSupplierFollowUp && in_array($order->status, ['issued', 'received'], true))
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4" aria-label="Tindak lanjut supplier">
            <h2 class="font-semibold text-amber-950">Laporan Gudang dan tindak lanjut Purchasing</h2>
            <p class="mt-1 text-sm text-amber-900">Barang rusak/tidak layak tetap tercatat di bukti penerimaan, tetapi tidak menambah stok. Sisa per item:</p>
            <?php if ($remainingPoQuantity > 0): ?>
                <ul class="mt-1 list-inside list-disc text-sm text-amber-950">
                    <?php foreach ($order->items as $item): ?>
                        <?php $itemRemaining = max(0, (float) $item->quantity - (float) ($receivedQuantities[$item->id] ?? 0)); ?>
                        <?php if ($itemRemaining > 0): ?><li><?= e($item->product->name) ?>: <?= e(\App\Support\QuantityFormatter::format($itemRemaining)) ?> <?= e($item->product->unit->symbol ?? '') ?></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="mt-1 text-sm text-amber-950">Tidak ada sisa kuantitas PO; Purchasing meninjau catatan barang tidak layak.</p>
            <?php endif; ?>
            @include('purchasing._supplier-follow-up-form')
        </section>
    @endif

    @if(!$isWarehouseStaff && $order->status === 'closed_with_variance')
        <section class="rounded-2xl border border-violet-200 bg-violet-50 p-4">
            <h2 class="font-semibold text-violet-950">PO ditutup dengan selisih oleh Purchasing</h2>
            <p class="mt-1 text-sm text-violet-900">{{ $order->supplier_follow_up_note }}</p>
            <p class="mt-1 text-xs text-violet-800">Dicatat {{ $order->supplier_follow_up_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB oleh {{ $order->supplierFollowUpBy?->name ?? 'Purchasing' }}. Penerimaan baru untuk PO ini sudah ditutup.</p>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><span class="material-symbols-outlined text-[18px]">receipt_long</span></span><div><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Dokumen pendukung</p><h2 class="text-sm font-semibold text-slate-900">Acuan faktur</h2></div></div>
        @if($invoice)
            <p class="mt-1 text-sm font-medium text-gray-800">{{ $invoice->invoice_number }} &middot; Tanggal {{ $invoice->invoice_date->format('d M Y') }}</p>
            <p class="mt-1 text-xs text-secondary">Jumlah faktur tercatat dari Purchasing. Cocokkan dengan hasil hitung fisik gudang.</p>
            @if($isWarehouseStaff)<a href="{{ route('purchase-invoices.document', $invoice) }}" class="mt-3 inline-flex rounded-lg border border-outline px-3 py-2 text-xs font-semibold text-primary">Lihat dokumen faktur</a>@endif
        @else
            <p class="mt-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Faktur belum ditautkan. Barang tetap dapat diterima dan dicatat ke saldo gudang; minta Purchasing menautkan invoice setelah dokumen tersedia agar valuasi dapat diperbarui.</p>
            @if(!$isWarehouseStaff && in_array($order->status, ['received', 'closed_with_variance'], true) && auth()->user()->hasPermissionTo('invoice.manage'))
                <a href="{{ route('purchase-invoices.create', ['purchase_order_id' => $order->id]) }}" class="mt-3 inline-flex w-full items-center justify-center rounded-xl bg-[#DC143C] px-4 py-3 text-sm font-semibold text-white">Buat faktur dari PO ini</a>
            @endif
        @endif
    </section>

    <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3"><div><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">Ringkasan item</p><h2 class="text-sm font-semibold text-slate-900">Cocokkan barang</h2><p class="mt-1 text-xs text-secondary">Isi hanya barang yang datang. Kosongkan barang yang belum datang.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">{{ $order->items->count() }} item</span></div>
        @foreach ($order->items as $item)
            @php
                $received = (float) ($receivedQuantities[$item->id] ?? 0);
                $remaining = max(0, (float) $item->quantity - $received);
                $unitSymbol = $item->product->unit->symbol ?? 'unit';
            @endphp
            <article class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 transition hover:border-slate-300">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">{{ $item->product->name }}</p>
                        <p class="text-xs text-secondary">{{ $item->product->sku }}</p>
                    </div>
                    <div class="shrink-0 text-right text-xs space-y-0.5">
                        <p class="text-secondary">Faktur: {{ $invoiceQuantities->has($item->product_id) ? \App\Support\QuantityFormatter::format($invoiceQuantities[$item->product_id]) . ' ' . $unitSymbol : 'Tidak dicantumkan' }}</p>
                        <p class="text-secondary">Sudah masuk: {{ \App\Support\QuantityFormatter::format($received) }} {{ $unitSymbol }}</p>
                        <p class="font-bold text-primary">Sisa PO: {{ \App\Support\QuantityFormatter::format($remaining) }} {{ $unitSymbol }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    @if ($order->status === 'issued' && auth()->user()->hasPermissionTo('stock.manage'))
        <form id="receipt-form-mobile" method="POST" enctype="multipart/form-data" action="{{ route('purchasing.orders.receive', $order) }}" class="space-y-4 rounded-2xl border border-rose-200 bg-white p-4 shadow-sm scroll-mt-6" data-confirm-title="Simpan hasil pemeriksaan?" data-confirm-message="Jumlah layak diterima akan langsung menambah stok gudang. Periksa jumlah, foto bukti, dan catatan selisih sebelum menyimpan." data-confirm-button="Ya, catat penerimaan">
            @csrf
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-[#DC143C]"><span class="material-symbols-outlined">inventory</span></span><div><p class="text-[9px] font-bold uppercase tracking-[0.14em] text-[#DC143C]">Penerimaan gudang</p><h2 class="text-sm font-semibold text-slate-900">Catat kedatangan</h2></div></div>
            <input type="hidden" name="idempotency_key" value="{{ $receiptSubmissionKey }}">
            @if($invoice)<input type="hidden" name="purchase_invoice_id" value="{{ $invoice->id }}">@endif
            <label class="block text-sm font-semibold">Tanggal diterima<input required type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border p-2.5 text-sm"></label>
            <label class="block rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm font-semibold text-slate-800">Foto bukti kedatangan supplier <span class="text-rose-600">*</span>
                <input required type="file" name="evidence_photos[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 block w-full text-xs font-normal text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#DC143C]">
                <span class="mt-1 block text-[11px] font-normal text-slate-500">Unggah 1–5 foto, format JPG/PNG/WebP, maksimal 5 MB per foto.</span>
            </label>
            <p class="text-xs text-secondary">Hitung jumlah datang dan kondisi barang. Hanya jumlah layak yang menambah saldo. Isi nomor batch dan kedaluwarsa pada panel setiap barang; datanya otomatis tersimpan bersama Stock In PO. Untuk barang rusak, selisih, atau kelebihan dari PO, isi catatan.</p>
            @foreach ($order->items as $item)
                @php
                    $received = (float) ($receivedQuantities[$item->id] ?? 0);
                    $remaining = max(0, (float) $item->quantity - $received);
                    $unitSymbol = $item->product->unit->symbol ?? 'unit';
                @endphp
                <div class="rounded-xl border border-outline bg-white p-4 space-y-3">
                    <div class="border-b border-outline pb-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-semibold text-sm">{{ $item->product->name }}</h3>
                            <span class="rounded bg-surface-container px-1.5 py-0.5 text-[10px] font-mono text-secondary">{{ $item->product->sku }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5 text-[11px]">
                            <span class="rounded bg-surface-container px-2 py-0.5 text-secondary">Faktur: <strong>{{ $invoiceQuantities->has($item->product_id) ? \App\Support\QuantityFormatter::format($invoiceQuantities[$item->product_id]) . ' ' . $unitSymbol : 'Tidak dicantumkan' }}</strong></span>
                            <span class="rounded bg-surface-container px-2 py-0.5 text-secondary">Sudah: <strong>{{ \App\Support\QuantityFormatter::format($received) }} {{ $unitSymbol }}</strong></span>
                            <span class="rounded bg-rose-50 px-2 py-0.5 font-semibold text-primary">Sisa PO: {{ \App\Support\QuantityFormatter::format($remaining) }} {{ $unitSymbol }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-secondary">Jumlah nyata layak masuk gudang ({{ $unitSymbol }})
                            <input type="number" min="0" step="0.001" name="items[{{ $item->id }}][accepted_quantity]" value="{{ old('items.'.$item->id.'.accepted_quantity') }}" placeholder="Contoh: {{ \App\Support\QuantityFormatter::formatInput($remaining) }}" class="mt-1 w-full rounded-lg border p-2.5 text-sm text-gray-900">
                        </label>
                    </div>
                    <details class="rounded-lg bg-gray-50 p-3">
                        <summary class="cursor-pointer text-xs font-semibold text-secondary">Ada barang rusak atau jumlah fisik berbeda?</summary>
                        <div class="mt-3 space-y-3">
                            <label class="block text-xs font-medium text-secondary">Jumlah fisik datang (opsional)
                                <input type="number" min="0" step="0.001" name="items[{{ $item->id }}][delivered_quantity]" value="{{ old('items.'.$item->id.'.delivered_quantity') }}" placeholder="Kosongkan jika sama dengan jumlah masuk stok" class="mt-1 w-full rounded-lg border p-2.5 text-sm text-gray-900">
                            </label>
                            <label class="block text-xs font-medium text-secondary">Catatan selisih / kerusakan
                                <textarea name="items[{{ $item->id }}][variance_note]" rows="2" maxlength="1000" placeholder="Contoh: 1 kemasan rusak; 1 unit ekstra disetujui Purchasing (nama/waktu)" class="mt-1 w-full rounded-lg border p-2.5 text-sm text-gray-900">{{ old('items.'.$item->id.'.variance_note') }}</textarea>
                            </label>
                        </div>
                    </details>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold text-slate-700">Batch &amp; kedaluwarsa @if($item->product->requires_expiry_tracking)<span class="text-rose-600">· wajib</span>@else<span class="font-normal text-slate-500">· opsional</span>@endif</p>
                        <p class="mt-1 text-[11px] text-slate-500">Staff Gudang menyalin nomor lot dan tanggal dari label barang yang datang.</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label class="text-xs font-medium text-secondary">Nomor batch @if($item->product->requires_expiry_tracking)<span class="text-rose-600">*</span>@endif<input @required($item->product->requires_expiry_tracking) name="items[{{ $item->id }}][batch_number]" value="{{ old('items.'.$item->id.'.batch_number') }}" maxlength="100" placeholder="Nomor pada kemasan" class="mt-1 w-full rounded-lg border p-2.5 text-sm text-gray-900">@error('items.'.$item->id.'.batch_number')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
                            <label class="text-xs font-medium text-secondary">Kedaluwarsa @if($item->product->requires_expiry_tracking)<span class="text-rose-600">*</span>@endif<input @required($item->product->requires_expiry_tracking) type="date" name="items[{{ $item->id }}][expiry_date]" value="{{ old('items.'.$item->id.'.expiry_date') }}" class="mt-1 w-full rounded-lg border p-2.5 text-sm text-gray-900">@error('items.'.$item->id.'.expiry_date')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
                        </div>
                    </div>
                </div>
            @endforeach
            <button class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white">Simpan hasil pemeriksaan</button>
        </form>
    @endif

    @if ($order->status === 'issued' && auth()->user()->hasPermissionTo('stock.manage') && ! $invoice)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Penerimaan tetap dapat dicatat sebelum dokumen invoice tersedia. Hubungkan invoice ke receipt setelah Purchasing menerima dokumennya.</p>
    @endif

    @if ($hasReceipts)
        <section class="rounded-2xl border border-outline bg-white p-4">
            <h2 class="font-semibold">Riwayat penerimaan</h2>
            <div class="mt-2 divide-y divide-outline">
                @foreach ($order->stockIn->sortByDesc('received_at') as $receipt)
                    <div class="py-3">
                        <a href="{{ route('inventory.stock-in.show', $receipt) }}" class="flex items-center justify-between gap-3 text-sm">
                            <span><strong>{{ $receipt->reference }}</strong><span class="block text-xs text-secondary">{{ $receipt->received_at->format('d M Y') }}</span>@if($receipt->purchase_order_id && ! $receipt->purchase_invoice_id)<span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800">Invoice pending</span>@endif</span>
                            <span class="font-semibold text-primary">Lihat Stock In &rarr;</span>
                        </a>
                        @foreach($receipt->items as $line)
                            @php
                                $delivered = (float) ($line->delivered_quantity ?? $line->quantity);
                                $notAccepted = max(0, $delivered - (float) $line->quantity);
                            @endphp
                            <div class="mt-2 rounded-lg {{ $notAccepted > 0 ? 'bg-amber-50' : 'bg-gray-50' }} p-3 text-xs">
                                <p class="font-semibold">{{ $line->product->name }}: datang {{ \App\Support\QuantityFormatter::format($delivered) }}, layak masuk stok {{ \App\Support\QuantityFormatter::format($line->quantity) }} {{ $line->product->unit->symbol ?? '' }}</p>
                    @if($notAccepted > 0)<p class="mt-1 font-semibold text-amber-900">Tidak masuk stok: {{ \App\Support\QuantityFormatter::format($notAccepted) }} {{ $line->product->unit->symbol ?? '' }}</p>@endif
                                @if($line->variance_note)<p class="mt-1 whitespace-pre-line text-gray-700">Catatan Gudang: {{ $line->variance_note }}</p>@endif
                            </div>
                        @endforeach
                        @include('purchasing._receipt-evidence', ['receipt' => $receipt])
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection
@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-6">
    <a href="{{ $isWarehouseStaff ? route('inventory.stock-in.purchase-orders') : route('purchasing.index') }}" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-[#DC143C]"><span aria-hidden="true">←</span> {{ $isWarehouseStaff ? 'Daftar penerimaan' : 'Pengadaan' }}</a>

    @if (session('status'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</p>
        @if (session('receipt_url'))
            <a href="{{ session('receipt_url') }}" class="-mt-3 block text-sm font-semibold text-[#DC143C]">Buka bukti Stock In {{ session('receipt_reference') }} &rarr;</a>
        @endif
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Penerimaan belum tersimpan.</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
        <div class="absolute inset-y-0 left-0 w-1.5 bg-[#DC143C]"></div>
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 pl-2"><p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#DC143C]">{{ $isWarehouseStaff ? 'Penerimaan gudang' : 'Purchase Order' }}</p><h1 class="mt-1 font-poppins text-3xl font-semibold tracking-tight text-slate-950">{{ $order->reference }}</h1><p class="mt-2 text-sm text-slate-500">{{ $order->supplier->name }} <span class="px-1.5 text-slate-300">/</span> {{ $order->location->name }}</p></div>
            <span class="shrink-0 rounded-full border px-3.5 py-2 text-xs font-bold {{ $orderStatusClass }}">{{ $orderStatusLabel }}</span>
        </div>
        <dl class="mt-6 grid grid-cols-2 gap-5 border-t border-slate-100 pt-5 text-sm md:grid-cols-4">
            <div><dt class="text-xs text-slate-400">Estimasi kedatangan</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $order->expected_delivery?->format('d M Y') ?? 'Belum ditentukan' }}</dd></div>
            <div><dt class="text-xs text-slate-400">Dibuat oleh</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $order->creator->name }}</dd></div>
            <div><dt class="text-xs text-slate-400">Jumlah jenis barang</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $order->items->count() }} item</dd></div>
            <div><dt class="text-xs text-slate-400">Penerimaan tercatat</dt><dd class="mt-1.5 font-semibold text-slate-800">{{ $order->stockIn->count() }} kali</dd></div>
        </dl>
        @unless($isWarehouseStaff)<p class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">Permintaan asal <a href="{{ route('purchasing.requests.show', $order->request) }}" class="ml-1 font-semibold text-[#DC143C]">{{ $order->request->reference }} <span aria-hidden="true">↗</span></a></p>@endunless
    </div>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Tahapan Purchase Order">
        <div class="flex items-center justify-between gap-3">
            <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Progres dokumen</p><h2 class="mt-1 text-sm font-semibold text-slate-900">Alur pesanan</h2></div>
            <span class="rounded-full bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-600">{{ $order->stockIn->count() }} penerimaan</span>
        </div>
        <ol class="mt-5 grid grid-cols-3">
            <li class="relative flex min-w-0 flex-col items-center text-center">
                <span class="absolute left-1/2 right-0 top-4 h-px bg-emerald-200" aria-hidden="true"></span>
                <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-4 ring-white"><span class="material-symbols-outlined text-[18px]">check</span></span>
                <span class="mt-2 max-w-full px-1 text-xs font-semibold leading-4 text-slate-800">PO diterbitkan</span>
            </li>
            <li class="relative flex min-w-0 flex-col items-center text-center">
                <span class="absolute left-1/2 right-0 top-4 h-px {{ $arrivalStageComplete ? 'bg-emerald-200' : 'bg-slate-200' }}" aria-hidden="true"></span>
                <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $arrivalStageComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }} ring-4 ring-white"><span class="material-symbols-outlined text-[18px]">{{ $arrivalStageComplete ? 'check' : 'local_shipping' }}</span></span>
                <span class="mt-2 max-w-full px-1 text-xs font-semibold leading-4 {{ $arrivalStageComplete ? 'text-slate-800' : 'text-slate-400' }}">Penerimaan</span>
            </li>
            <li class="flex min-w-0 flex-col items-center text-center">
                <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $invoice ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400' }} ring-4 ring-white"><span class="material-symbols-outlined text-[18px]">{{ $invoice ? 'check' : 'receipt_long' }}</span></span>
                <span class="mt-2 max-w-full px-1 text-xs font-semibold leading-4 {{ $invoice ? 'text-slate-800' : 'text-slate-400' }}">Faktur</span>
            </li>
        </ol>
    </section>

    @if ($order->status === 'issued')
        <section class="rounded-2xl border {{ $canReceiveOrder ? 'border-rose-200 bg-rose-50/80' : 'border-amber-200 bg-amber-50/80' }} p-5 shadow-sm" aria-label="Langkah berikutnya">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] {{ $canReceiveOrder ? 'text-[#DC143C]' : 'text-amber-800' }}">Tindakan berikutnya</p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $canReceiveOrder ? 'Periksa dan catat barang yang datang' : ($hasReceipts ? 'Tindak lanjuti sisa / selisih dengan supplier' : 'Menunggu penerimaan oleh Gudang') }}</h2>
                    <p class="mt-1 text-sm {{ $canReceiveOrder ? 'text-gray-700' : 'text-amber-900' }}">{{ $canReceiveOrder ? 'Catat jumlah datang dan jumlah layak secara terpisah. Hanya jumlah layak yang menambah stok.' : ($hasReceipts ? 'Gudang sudah mencatat penerimaan sebagian. Purchasing perlu menghubungi supplier dan mencatat janji kirim/pengganti atau penyelesaian klaim di bawah. PO tetap terbuka sampai sisa diterima atau ditutup dengan alasan.' : 'Staf Gudang melanjutkan pemeriksaan fisik dari PO ini. Jika jumlah atau kondisi berbeda, catat sebagai selisih agar Purchasing dapat menindaklanjuti supplier.') }}</p>
                </div>
                @if ($canReceiveOrder)
                    <a href="#receipt-form-desktop" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-bold text-white shadow-sm">Lanjut catat penerimaan <span aria-hidden="true">↓</span></a>
                @endif
            </div>
        </section>
    @elseif ($canCreateOrderInvoice)
        <section class="rounded-2xl border-2 border-[#DC143C] bg-rose-50 p-5" aria-label="Langkah berikutnya">
            <p class="text-xs font-bold uppercase tracking-wide text-[#DC143C]">Langkah berikutnya</p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-4">
                <div><h2 class="font-semibold">Barang sudah diterima. Tautkan faktur</h2><p class="mt-1 text-sm text-gray-700">Buat faktur dari PO ini agar dokumen dan nilai pembelian tercatat.</p></div>
                <a href="{{ route('purchase-invoices.create', ['purchase_order_id' => $order->id]) }}" class="inline-flex shrink-0 items-center rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-bold text-white shadow-sm">Buat faktur dari PO ini <span class="ml-2" aria-hidden="true">→</span></a>
            </div>
        </section>
    @endif

    @if(!$isWarehouseStaff && $hasReceipts && ($remainingPoQuantity > 0 || $hasReceiptVariance) && $canManageSupplierFollowUp && in_array($order->status, ['issued', 'received'], true))
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5" aria-label="Tindak lanjut supplier">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="font-semibold text-amber-950">Laporan Gudang dan tindak lanjut Purchasing</h2><p class="mt-1 text-sm text-amber-900">Barang rusak/tidak layak tidak menambah saldo. Sisa per item:</p>
                    <?php if ($remainingPoQuantity > 0): ?>
                        <ul class="mt-1 list-inside list-disc text-sm text-amber-950">
                            <?php foreach ($order->items as $item): ?>
                                <?php $itemRemaining = max(0, (float) $item->quantity - (float) ($receivedQuantities[$item->id] ?? 0)); ?>
                                <?php if ($itemRemaining > 0): ?><li><?= e($item->product->name) ?>: <?= e(\App\Support\QuantityFormatter::format($itemRemaining)) ?> <?= e($item->product->unit->symbol ?? '') ?></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mt-1 text-sm text-amber-950">Tidak ada sisa kuantitas PO; Purchasing meninjau catatan barang tidak layak.</p>
                    <?php endif; ?>
                </div>
            </div>
            @include('purchasing._supplier-follow-up-form')
        </section>
    @endif

    @if(!$isWarehouseStaff && $order->status === 'closed_with_variance')
        <section class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
            <h2 class="font-semibold text-violet-950">PO ditutup dengan selisih oleh Purchasing</h2>
            <p class="mt-1 text-sm text-violet-900">{{ $order->supplier_follow_up_note }}</p>
            <p class="mt-1 text-xs text-violet-800">Dicatat {{ $order->supplier_follow_up_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB oleh {{ $order->supplierFollowUpBy?->name ?? 'Purchasing' }}. Penerimaan baru untuk PO ini sudah ditutup.</p>
        </section>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><span class="material-symbols-outlined">receipt_long</span></span><div><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Dokumen pendukung</p><h2 class="text-sm font-semibold text-slate-900">Acuan faktur</h2></div></div>
        @if($invoice)
            <p class="mt-1 text-sm font-medium text-gray-800">{{ $invoice->invoice_number }} &middot; Tanggal {{ $invoice->invoice_date->format('d M Y') }}</p>
            <p class="mt-1 text-sm text-gray-500">Jumlah faktur tercatat dari Purchasing. Cocokkan dengan hitungan fisik sebelum menyimpan penerimaan.</p>
            @if($isWarehouseStaff)<a href="{{ route('purchase-invoices.document', $invoice) }}" class="mt-3 inline-flex rounded-lg border px-3 py-2 text-sm font-semibold text-[#DC143C]">Lihat dokumen faktur</a>@endif
        @else
            <p class="mt-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Faktur belum ditautkan. Barang tetap dapat diterima dan dicatat ke saldo gudang; Purchasing dapat menautkan invoice setelah dokumen tersedia.</p>
            @if(!$isWarehouseStaff && in_array($order->status, ['received', 'closed_with_variance'], true) && auth()->user()->hasPermissionTo('invoice.manage'))
                <a href="{{ route('purchase-invoices.create', ['purchase_order_id' => $order->id]) }}" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white">Buat faktur dari PO ini</a>
            @endif
        @endif
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Ringkasan item</p><h2 class="mt-0.5 text-sm font-semibold text-slate-900">Pemeriksaan barang</h2><p class="mt-1 text-xs text-slate-500">Cocokkan jumlah dari supplier sebelum stok ditambahkan.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $order->items->count() }} item</span></div>
        <div class="divide-y divide-slate-100">
            @foreach ($order->items as $item)
                @php
                    $received = (float) ($receivedQuantities[$item->id] ?? 0);
                    $remaining = max(0, (float) $item->quantity - $received);
                    $unitSymbol = $item->product->unit->symbol ?? 'unit';
                @endphp
                <div class="grid grid-cols-[minmax(180px,1fr)_150px_150px_150px] items-center gap-4 px-5 py-4 text-sm transition hover:bg-slate-50/70">
                    <div class="min-w-0"><p class="truncate font-semibold text-slate-900">{{ $item->product->name }}</p><p class="mt-0.5 text-[11px] text-slate-500">{{ $item->product->sku }}</p></div>
                    <div><p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Di faktur</p><p class="mt-1 font-semibold text-slate-700">{{ $invoiceQuantities->has($item->product_id) ? \App\Support\QuantityFormatter::format($invoiceQuantities[$item->product_id]) . ' ' . $unitSymbol : '—' }}</p></div>
                    <div><p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Diterima layak</p><p class="mt-1 font-semibold text-slate-700">{{ \App\Support\QuantityFormatter::format($received) }} {{ $unitSymbol }}</p></div>
                    <div><p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Sisa PO</p><p class="mt-1 font-semibold text-[#DC143C]">{{ \App\Support\QuantityFormatter::format($remaining) }} {{ $unitSymbol }}</p></div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($order->status === 'issued' && auth()->user()->hasPermissionTo('stock.manage'))
        <form id="receipt-form-desktop" method="POST" enctype="multipart/form-data" action="{{ route('purchasing.orders.receive', $order) }}" class="space-y-5 rounded-3xl border border-rose-200 bg-white p-6 shadow-sm scroll-mt-6" data-confirm-title="Simpan hasil pemeriksaan?" data-confirm-message="Jumlah layak diterima akan langsung menambah stok gudang. Periksa jumlah, foto bukti, dan catatan selisih sebelum menyimpan." data-confirm-button="Ya, catat penerimaan">
            @csrf
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-[#DC143C]"><span class="material-symbols-outlined text-[22px]">inventory</span></span><div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">Penerimaan gudang</p><h2 class="mt-0.5 text-base font-semibold text-slate-900">Catat kedatangan supplier</h2><p class="mt-0.5 text-xs text-slate-500">Jumlah layak akan menambah saldo; foto dan catatan menjadi bukti penerimaan.</p></div></div>
            <input type="hidden" name="idempotency_key" value="{{ $receiptSubmissionKey }}">
            @if($invoice)<input type="hidden" name="purchase_invoice_id" value="{{ $invoice->id }}">@endif
            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold">Tanggal diterima<input required type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}" class="mt-1 block rounded-lg border p-2.5 text-sm"></label>
                <p class="self-end text-sm text-gray-500">Hitung jumlah datang dan kondisi barang. Hanya jumlah layak menambah saldo. Barang rusak atau selisih wajib dicatat; kelebihan dari PO hanya diterima dengan persetujuan Purchasing yang dicatat.</p>
            </div>
            <label class="block rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-800">Foto bukti kedatangan supplier <span class="text-rose-600">*</span>
                <input required type="file" name="evidence_photos[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-2 block w-full text-sm font-normal text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#DC143C]">
                <span class="mt-1 block text-xs font-normal text-slate-500">Unggah 1–5 foto, format JPG/PNG/WebP, maksimal 5 MB per foto.</span>
            </label>
            @foreach ($order->items as $item)
                @php
                    $received = (float) ($receivedQuantities[$item->id] ?? 0);
                    $remaining = max(0, (float) $item->quantity - $received);
                    $unitSymbol = $item->product->unit->symbol ?? 'unit';
                @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900 text-base">{{ $item->product->name }}</h3>
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-600">{{ $item->product->sku }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">Total pesanan PO: <strong class="text-gray-700">{{ \App\Support\QuantityFormatter::format($item->quantity) }} {{ $unitSymbol }}</strong></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700">
                                <span class="text-slate-400">Di Faktur:</span>
                                <span class="font-semibold">{{ $invoiceQuantities->has($item->product_id) ? \App\Support\QuantityFormatter::format($invoiceQuantities[$item->product_id]) . ' ' . $unitSymbol : 'Tidak dicantumkan' }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700">
                                <span class="text-slate-400">Sudah Masuk:</span>
                                <span class="font-semibold">{{ \App\Support\QuantityFormatter::format($received) }} {{ $unitSymbol }}</span>
                            </div>
                            <div class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs text-rose-700">
                                <span class="text-rose-500">Sisa PO:</span>
                                <span class="font-bold">{{ \App\Support\QuantityFormatter::format($remaining) }} {{ $unitSymbol }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">Jumlah nyata layak masuk gudang ({{ $unitSymbol }})</label>
                            <span class="text-[11px] text-gray-400">Kosongkan jika belum ada barang datang</span>
                        </div>
                        <input type="number" min="0" step="0.001" name="items[{{ $item->id }}][accepted_quantity]" value="{{ old('items.'.$item->id.'.accepted_quantity') }}" placeholder="Contoh: {{ \App\Support\QuantityFormatter::formatInput($remaining) }}" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-900 placeholder:font-normal placeholder:text-gray-400 focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition">
                    </div>

                    <div class="space-y-2 pt-1">
                        <details class="group rounded-xl border border-gray-100 bg-gray-50/80 p-3.5 transition-colors">
                            <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-gray-700 select-none">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-gray-400 group-open:rotate-180 transition-transform">expand_more</span>
                                    <span>Ada barang rusak atau jumlah fisik berbeda? (Opsional)</span>
                                </span>
                                <span class="text-[11px] font-normal text-gray-400">Pencatatan selisih</span>
                            </summary>
                            <div class="mt-3 grid gap-3 pt-2 border-t border-gray-200/60 md:grid-cols-2">
                                <label class="text-xs font-medium text-gray-600">Jumlah fisik datang (opsional)
                                    <input type="number" min="0" step="0.001" name="items[{{ $item->id }}][delivered_quantity]" value="{{ old('items.'.$item->id.'.delivered_quantity') }}" placeholder="Kosongkan jika sama dengan jumlah masuk stok" class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm">
                                </label>
                                <label class="text-xs font-medium text-gray-600">Catatan selisih / kerusakan
                                    <textarea name="items[{{ $item->id }}][variance_note]" rows="2" maxlength="1000" placeholder="Contoh: 1 kemasan rusak; 1 unit ekstra disetujui Purchasing (nama/waktu)" class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm">{{ old('items.'.$item->id.'.variance_note') }}</textarea>
                                </label>
                            </div>
                        </details>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <p class="text-xs font-semibold text-gray-700">Batch &amp; kedaluwarsa @if($item->product->requires_expiry_tracking)<span class="text-rose-600">· wajib</span>@else<span class="font-normal text-gray-500">· opsional</span>@endif</p>
                                <span class="text-[11px] text-gray-500">Diisi Staff Gudang dari label barang; tersimpan otomatis ke Stock In.</span>
                            </div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <label class="text-xs font-medium text-gray-600">Nomor batch @if($item->product->requires_expiry_tracking)<span class="text-rose-600">*</span>@endif
                                    <input @required($item->product->requires_expiry_tracking) name="items[{{ $item->id }}][batch_number]" value="{{ old('items.'.$item->id.'.batch_number') }}" maxlength="100" placeholder="Nomor pada kemasan" class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm">
                                    @error('items.'.$item->id.'.batch_number')<small class="mt-1 block text-rose-700">{{ $message }}</small>@enderror
                                </label>
                                <label class="text-xs font-medium text-gray-600">Kedaluwarsa @if($item->product->requires_expiry_tracking)<span class="text-rose-600">*</span>@endif
                                    <input @required($item->product->requires_expiry_tracking) type="date" name="items[{{ $item->id }}][expiry_date]" value="{{ old('items.'.$item->id.'.expiry_date') }}" class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm">
                                    @error('items.'.$item->id.'.expiry_date')<small class="mt-1 block text-rose-700">{{ $message }}</small>@enderror
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="flex justify-end"><button class="rounded-lg bg-[#DC143C] px-5 py-2.5 text-sm font-semibold text-white">Simpan hasil pemeriksaan</button></div>
        </form>
    @endif

    @if ($order->status === 'issued' && auth()->user()->hasPermissionTo('stock.manage') && ! $invoice)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Penerimaan tetap dapat dicatat sebelum dokumen invoice tersedia. Hubungkan invoice ke receipt setelah Purchasing menerima dokumennya.</p>
    @endif

    @if ($hasReceipts)
        <div class="rounded-2xl border border-[#EAEAEA] bg-white p-5">
            <h2 class="font-semibold">Riwayat penerimaan</h2>
            <div class="mt-2 divide-y">
                @foreach ($order->stockIn->sortByDesc('received_at') as $receipt)
                    <div class="py-3">
                        <a href="{{ route('inventory.stock-in.show', $receipt) }}" class="flex items-center justify-between gap-3 text-sm hover:text-primary">
                            <span><strong>{{ $receipt->reference }}</strong><span class="ml-2 text-gray-500">{{ $receipt->received_at->format('d M Y') }}</span>@if($receipt->purchase_order_id && ! $receipt->purchase_invoice_id)<span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Invoice pending</span>@endif</span>
                            <span class="font-semibold">Lihat Stock In &rarr;</span>
                        </a>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            @foreach($receipt->items as $line)
                                @php
                                    $delivered = (float) ($line->delivered_quantity ?? $line->quantity);
                                    $notAccepted = max(0, $delivered - (float) $line->quantity);
                                @endphp
                                <div class="rounded-lg {{ $notAccepted > 0 ? 'bg-amber-50' : 'bg-gray-50' }} p-3 text-xs">
                                    <p class="font-semibold">{{ $line->product->name }}: datang {{ \App\Support\QuantityFormatter::format($delivered) }}, layak masuk stok {{ \App\Support\QuantityFormatter::format($line->quantity) }} {{ $line->product->unit->symbol ?? '' }}</p>
                                    @if($notAccepted > 0)<p class="mt-1 font-semibold text-amber-900">Tidak masuk stok: {{ \App\Support\QuantityFormatter::format($notAccepted) }} {{ $line->product->unit->symbol ?? '' }}</p>@endif
                                    @if($line->variance_note)<p class="mt-1 whitespace-pre-line text-gray-700">Catatan Gudang: {{ $line->variance_note }}</p>@endif
                                </div>
                            @endforeach
                        </div>
                        @include('purchasing._receipt-evidence', ['receipt' => $receipt])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection
