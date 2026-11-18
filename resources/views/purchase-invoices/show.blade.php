@extends('layouts.dashboard')
@php
    $pageTitle = 'Faktur '.$invoice->invoice_number.' — Technolife';
    $statusStyle = [
        'draft' => 'bg-slate-100 text-slate-700 border border-slate-200',
        'submitted' => 'bg-amber-50 text-amber-800 border border-amber-200',
        'accepted' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-800 border border-rose-200',
        'revision_required' => 'bg-orange-50 text-orange-800 border border-orange-200',
    ][$invoice->status] ?? 'bg-slate-100 text-slate-700';
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary" href="{{ route('purchase-invoices.index') }}">&larr; Daftar faktur</a>
    
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-4 space-y-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h1 class="font-poppins text-xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h1>
                <p class="mt-1 text-xs text-gray-500">{{ $invoice->supplier->name }} &middot; {{ $invoice->invoice_date->format('d M Y') }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ str_replace('_',' ',ucfirst($invoice->status)) }}</span>
        </div>
        <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-500">Total sesuai dokumen sumber</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900">Rp {{ number_format((float)$invoice->document_total, 2, ',', '.') }}</p>
        </div>
        <a class="inline-flex items-center justify-center gap-1.5 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition" href="{{ route('purchase-invoices.document',$invoice) }}">
            <span class="material-symbols-outlined text-[16px]">description</span>
            Buka dokumen faktur
        </a>
        @if($invoice->order)
            <p class="text-xs text-gray-600 border-t border-gray-100 pt-2">
                PO: <a class="font-semibold text-primary underline" href="{{ route('purchasing.orders.show',$invoice->order) }}">{{ $invoice->order->reference }}</a> &middot; {{ $invoice->order->location->name }}
            </p>
        @endif
    </section>

    <section class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4">
        <div class="flex justify-between items-center gap-3 border-b border-gray-100 pb-2">
            <h2 class="font-semibold text-sm text-gray-900">Status Pembayaran</h2>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ str_replace('_',' ',ucfirst($paymentStatus)) }}</span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-lg bg-gray-50 p-2">
                <p class="text-gray-500">Terbayar</p>
                <p class="font-bold text-emerald-700 mt-0.5">Rp {{ number_format($paidTotal, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-2">
                <p class="text-gray-500">Sisa Tagihan</p>
                <p class="font-bold text-rose-700 mt-0.5">Rp {{ number_format($outstanding, 2, ',', '.') }}</p>
            </div>
        </div>
        @include('purchase-invoices._payment-ledger', ['invoice' => $invoice, 'canPay' => $canPay])
        @if($canPay && $outstanding > 0.005)
            <form method="POST" action="{{ route('purchase-invoices.payments.store',$invoice) }}" class="space-y-2 border-t border-gray-100 pt-3">
                @csrf
                <p class="text-xs font-semibold text-gray-700">Catat Pembayaran Baru</p>
                <label class="block text-xs text-gray-600">Tanggal
                    <input required type="date" name="paid_at" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs">
                </label>
                <label class="block text-xs text-gray-600">Jumlah (maks. Rp {{ number_format($outstanding, 2, ',', '.') }})
                    <input required type="number" min="0.01" max="{{ number_format($outstanding, 2, '.', '') }}" step="0.01" name="amount" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs font-semibold">
                </label>
                <label class="block text-xs text-gray-600">Metode
                    <select name="method" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs">
                        <option value="bank_transfer">Transfer bank</option>
                        <option value="cash">Tunai</option>
                        <option value="other">Lainnya</option>
                    </select>
                </label>
                <label class="block text-xs text-gray-600">Referensi / No Bukti
                    <textarea name="reference" rows="1" maxlength="120" placeholder="No transfer / resi" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs"></textarea>
                </label>
                <button class="w-full rounded-xl bg-primary py-2.5 text-xs font-semibold text-white shadow-sm">Catat pembayaran</button>
            </form>
        @endif
    </section>

    <section class="space-y-2">
        <h2 class="font-semibold text-sm text-gray-900">Rincian item</h2>
        @foreach($invoice->items as $line)
            @php
                $qtyFormatted = \App\Support\QuantityFormatter::format($line->quantity);
                $unitStr = $line->unit_label ?: ($line->product->unit->symbol ?? 'unit');
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-3 space-y-1">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-sm text-gray-900">{{ $line->product->name }}</p>
                    <p class="text-right text-xs font-semibold text-gray-900">Rp {{ number_format((float)$line->quantity * (float)$line->unit_price, 2, ',', '.') }}</p>
                </div>
                <p class="text-xs text-gray-500">{{ $qtyFormatted }} {{ $unitStr }} &times; Rp {{ number_format((float)$line->unit_price, 2, ',', '.') }}</p>
            </div>
        @endforeach
    </section>

    <section class="space-y-2 rounded-2xl border border-gray-200 bg-white p-4">
        <h2 class="font-semibold text-sm text-gray-900">Perbandingan penerimaan</h2>
        @if($invoice->order)
            <p class="text-xs text-gray-500">Bandingkan dengan total seluruh receipt PO, termasuk yang tertaut ke faktur lain. Selisih hanya informasi dan tidak mengubah saldo.</p>
            @include('purchase-invoices._comparison-rows', ['comparison' => $comparison])
        @else
            <p class="text-sm text-gray-500">Faktur belum ditautkan ke PO/Stock In.</p>
        @endif
    </section>

    @include('purchase-invoices._total-breakdown', ['invoice' => $invoice])
    @include('purchase-invoices._link-receipts')

    @if($invoice->review_note)
        <section class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-sm text-orange-950">
            <strong>Catatan Finance</strong>
            @if($invoice->review_category)
                <p class="text-xs text-orange-800 mt-0.5">Kategori: {{ str_replace('_',' ',$invoice->review_category) }}</p>
            @endif
            <p class="mt-1 text-xs">{{ $invoice->review_note }}</p>
            @if($invoice->reviewer)
                <p class="mt-2 text-[11px] text-orange-700">&mdash; {{ $invoice->reviewer->name }} &middot; {{ $invoice->reviewed_at?->format('d M Y H:i') }}</p>
            @endif
        </section>
    @endif

    <details class="rounded-xl border border-gray-200 bg-white p-4">
        <summary class="cursor-pointer text-sm font-semibold text-gray-700">Riwayat perubahan ({{ $auditTrail->count() }})</summary>
        <div class="mt-3 space-y-3">
            @foreach($auditTrail as $entry)
                <div class="border-t border-gray-100 pt-3 text-xs">
                    <p class="font-semibold text-gray-800">{{ str_replace(['purchase_invoice.','_'],['',' '],$entry->action) }} &middot; {{ $entry->actor?->name ?? 'Pengguna dihapus' }}</p>
                    <p class="text-gray-400 mt-0.5">{{ $entry->created_at->format('d M Y H:i:s') }}</p>
                    <pre class="mt-2 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-2 text-[10px] text-gray-600">{{ json_encode($entry->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endforeach
        </div>
    </details>

    @if($canManage && in_array($invoice->status,['draft','revision_required']))
        <div class="flex gap-2">
            <a href="{{ route('purchase-invoices.edit',$invoice) }}" class="flex-1 rounded-xl border border-gray-300 bg-white p-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Ubah draft</a>
            <form class="flex-1" method="POST" action="{{ route('purchase-invoices.submit',$invoice) }}">
                @csrf
                <button class="w-full rounded-xl bg-[#DC143C] p-3 text-sm font-semibold text-white hover:bg-red-700 shadow-sm">Kirim ke Finance</button>
            </form>
        </div>
    @endif

    @if($canReview && $invoice->status==='submitted')
        <form method="POST" action="{{ route('purchase-invoices.review',$invoice) }}" class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4">
            @csrf
            <h2 class="font-semibold text-sm text-gray-900">Keputusan Finance</h2>
            <label class="block text-xs text-gray-600">Kategori (wajib untuk tolak/revisi)
                <select name="review_category" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs">
                    <option value="">Pilih jika menolak/revisi</option>
                    <option value="quantity">Kuantitas</option>
                    <option value="price">Harga</option>
                    <option value="supplier_po">Supplier / PO</option>
                    <option value="invalid_document">Dokumen tidak valid</option>
                    <option value="damaged_expired">Barang rusak / kedaluwarsa</option>
                    <option value="other">Lainnya</option>
                </select>
            </label>
            <label class="block text-xs text-gray-600">Catatan keputusan (wajib untuk tolak/revisi; dan saat menerima selisih total)
                <textarea name="review_note" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs"></textarea>
            </label>
            <div class="grid grid-cols-3 gap-2 pt-1">
                <button name="decision" value="accepted" class="rounded-xl bg-emerald-600 p-2 text-xs font-semibold text-white hover:bg-emerald-700">Terima</button>
                <button name="decision" value="revision_required" class="rounded-xl bg-amber-500 p-2 text-xs font-semibold text-white hover:bg-amber-600">Minta revisi</button>
                <button name="decision" value="rejected" class="rounded-xl bg-rose-600 p-2 text-xs font-semibold text-white hover:bg-rose-700">Tolak</button>
            </div>
        </form>
    @endif
    <p class="text-xs text-gray-400">Keputusan faktur tidak mencatat penerimaan barang atau pembayaran.</p>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('purchase-invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#DC143C] hover:underline">&larr; Daftar faktur</a>
    
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[1.4fr_0.8fr]">
        <section class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Faktur Pembelian</p>
                        <h1 class="mt-1 font-poppins text-2xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h1>
                        <p class="mt-1 text-sm text-gray-600">{{ $invoice->supplier->name }} &middot; {{ $invoice->invoice_date->format('d M Y') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusStyle }}">{{ str_replace('_',' ',ucfirst($invoice->status)) }}</span>
                </div>
                <div class="mt-4 flex flex-wrap items-end justify-between gap-4 border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Total sesuai dokumen</p>
                        <p class="mt-1 text-3xl font-bold text-gray-900">Rp {{ number_format((float)$invoice->document_total, 2, ',', '.') }}</p>
                    </div>
                    <a href="{{ route('purchase-invoices.document',$invoice) }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition shadow-2xs">
                        <span class="material-symbols-outlined text-[18px]">description</span>
                        Buka {{ $invoice->document_name }}
                    </a>
                </div>
                @if($invoice->order)
                    <p class="mt-3 border-t border-gray-100 pt-3 text-sm text-gray-600">
                        Purchase Order: <a class="font-semibold text-[#DC143C] hover:underline" href="{{ route('purchasing.orders.show',$invoice->order) }}">{{ $invoice->order->reference }}</a> &middot; Lokasi penerimaan: <strong>{{ $invoice->order->location->name }}</strong>
                    </p>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="border-b border-gray-100 p-5">
                    <h2 class="font-semibold text-gray-900">Rincian faktur</h2>
                    <p class="mt-1 text-xs text-gray-500">Harga disimpan sesuai input dokumen; total di bawah mengikuti angka total pada faktur sumber.</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($invoice->items as $line)
                        @php
                            $qtyFormatted = \App\Support\QuantityFormatter::format($line->quantity);
                            $unitStr = $line->unit_label ?: ($line->product->unit->symbol ?? 'unit');
                        @endphp
                        <div class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50/50 transition">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $line->product->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $line->product->sku }} &middot; <span class="font-medium text-gray-700">{{ $qtyFormatted }} {{ $unitStr }}</span> &times; Rp {{ number_format((float)$line->unit_price, 2, ',', '.') }}</p>
                            </div>
                            <p class="text-right text-sm font-bold text-gray-900">
                                Rp {{ number_format((float)$line->quantity * (float)$line->unit_price, 2, ',', '.') }}
                                <span class="block text-[11px] font-normal text-gray-400">subtotal baris</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs">
                <h2 class="font-semibold text-gray-900">Pemeriksaan terhadap Stock In</h2>
                @if($invoice->order)
                    <p class="mt-1 text-xs text-gray-500">Perbandingan memakai seluruh receipt PO, termasuk receipt yang tertaut ke faktur lain. Selisih tidak otomatis mengubah transaksi atau saldo.</p>
                    <div class="mt-3 divide-y divide-gray-100">
                        @include('purchase-invoices._comparison-rows', ['comparison' => $comparison])
                    </div>
                @else
                    <p class="mt-2 text-sm text-gray-500">Tidak ditautkan ke PO; belum ada pembanding penerimaan.</p>
                @endif
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="font-semibold text-gray-900">Pembayaran</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{{ str_replace('_',' ',ucfirst($paymentStatus)) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-xl bg-gray-50 p-2.5 border border-gray-100">
                        <p class="text-gray-500">Terbayar</p>
                        <p class="font-bold text-emerald-700 text-sm mt-0.5">Rp {{ number_format($paidTotal, 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-2.5 border border-gray-100">
                        <p class="text-gray-500">Sisa Tagihan</p>
                        <p class="font-bold text-rose-700 text-sm mt-0.5">Rp {{ number_format($outstanding, 2, ',', '.') }}</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @include('purchase-invoices._payment-ledger', ['invoice' => $invoice, 'canPay' => $canPay])
                </div>
                @if($canPay && $outstanding > 0.005)
                    <form method="POST" action="{{ route('purchase-invoices.payments.store',$invoice) }}" class="space-y-3 border-t border-gray-100 pt-3">
                        @csrf
                        <p class="text-xs font-semibold text-gray-800">Catat Pembayaran Baru</p>
                        <label class="block text-xs text-gray-600">Tanggal
                            <input required type="date" name="paid_at" value="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-xs">
                        </label>
                        <label class="block text-xs text-gray-600">Jumlah (maks. Rp {{ number_format($outstanding, 2, ',', '.') }})
                            <input required type="number" min="0.01" max="{{ number_format($outstanding, 2, '.', '') }}" step="0.01" name="amount" class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-xs font-semibold">
                        </label>
                        <label class="block text-xs text-gray-600">Metode
                            <select name="method" class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-xs">
                                <option value="bank_transfer">Transfer bank</option>
                                <option value="cash">Tunai</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </label>
                        <label class="block text-xs text-gray-600">Referensi
                            <input name="reference" maxlength="120" placeholder="No transfer / resi" class="mt-1 block w-full rounded-lg border border-gray-300 p-2 text-xs">
                        </label>
                        <button class="w-full rounded-xl bg-[#DC143C] p-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition">Catat pembayaran</button>
                    </form>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs space-y-2">
                <h2 class="font-semibold text-gray-900">Status Proses</h2>
                <p class="text-xs text-gray-600">Dibuat: <strong class="text-gray-800">{{ $invoice->creator->name }}</strong> &middot; {{ $invoice->created_at->format('d M Y H:i') }}</p>
                @if($invoice->submitted_at)
                    <p class="text-xs text-gray-600">Dikirim ke Finance: {{ $invoice->submitted_at->format('d M Y H:i') }}</p>
                @endif
                @if($invoice->reviewer)
                    <p class="text-xs text-gray-600">Ditinjau: <strong class="text-gray-800">{{ $invoice->reviewer->name }}</strong> &middot; {{ $invoice->reviewed_at?->format('d M Y H:i') }}</p>
                @endif
                @include('purchase-invoices._total-breakdown', ['invoice' => $invoice])
                @include('purchase-invoices._link-receipts')
                @if($invoice->review_note)
                    <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs text-orange-950">
                        <p class="font-semibold">Catatan Finance</p>
                        @if($invoice->review_category)
                            <p class="text-[11px] text-orange-800 mt-0.5">Kategori: {{ str_replace('_',' ',$invoice->review_category) }}</p>
                        @endif
                        <p class="mt-1">{{ $invoice->review_note }}</p>
                    </div>
                @endif
            </div>

            <details class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs">
                <summary class="cursor-pointer font-semibold text-sm text-gray-700 select-none">Riwayat perubahan ({{ $auditTrail->count() }})</summary>
                <div class="mt-3 space-y-3">
                    @foreach($auditTrail as $entry)
                        <div class="border-t border-gray-100 pt-3 text-xs">
                            <p class="font-semibold text-gray-800">{{ str_replace(['purchase_invoice.','_'],['',' '],$entry->action) }}</p>
                            <p class="mt-0.5 text-gray-400">{{ $entry->actor?->name ?? 'Pengguna dihapus' }} &middot; {{ $entry->created_at->format('d M Y H:i:s') }}</p>
                            <pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-2.5 text-[10px] text-gray-600">{{ json_encode($entry->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endforeach
                </div>
            </details>

            @if($canManage && in_array($invoice->status,['draft','revision_required']))
                <div class="flex gap-2">
                    <a href="{{ route('purchase-invoices.edit',$invoice) }}" class="flex-1 rounded-xl border border-gray-300 bg-white p-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">Ubah draft</a>
                    <form class="flex-1" method="POST" action="{{ route('purchase-invoices.submit',$invoice) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-[#DC143C] p-3 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition">Kirim ke Finance</button>
                    </form>
                </div>
            @endif

            @if($canReview && $invoice->status==='submitted')
                <form method="POST" action="{{ route('purchase-invoices.review',$invoice) }}" class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-xs">
                    @csrf
                    <h2 class="font-semibold text-gray-900 text-sm">Keputusan Finance</h2>
                    <label class="block text-xs text-gray-600">Kategori (wajib saat menolak/minta revisi)
                        <select name="review_category" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs">
                            <option value="">Pilih kategori</option>
                            <option value="quantity">Kuantitas</option>
                            <option value="price">Harga</option>
                            <option value="supplier_po">Supplier / PO</option>
                            <option value="invalid_document">Dokumen tidak valid</option>
                            <option value="damaged_expired">Barang rusak / kedaluwarsa</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </label>
                    <label class="block text-xs text-gray-600">Catatan (wajib saat menolak/minta revisi; dan saat menerima selisih total)
                        <textarea name="review_note" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-xs"></textarea>
                    </label>
                    <div class="space-y-2 pt-1">
                        <button name="decision" value="accepted" class="w-full rounded-xl bg-emerald-600 p-2.5 text-xs font-semibold text-white hover:bg-emerald-700 transition">Terima faktur</button>
                        <button name="decision" value="revision_required" class="w-full rounded-xl bg-amber-500 p-2.5 text-xs font-semibold text-white hover:bg-amber-600 transition">Minta revisi</button>
                        <button name="decision" value="rejected" class="w-full rounded-xl bg-rose-600 p-2.5 text-xs font-semibold text-white hover:bg-rose-700 transition">Tolak faktur</button>
                    </div>
                </form>
            @endif

            <div class="rounded-xl bg-slate-50 border border-slate-200/60 p-3.5 text-xs leading-relaxed text-gray-500">
                Status faktur terpisah dari penerimaan barang dan pembayaran. Penerimaan tetap dicatat melalui Stock In.
            </div>
        </aside>
    </div>
</section>
@endsection
