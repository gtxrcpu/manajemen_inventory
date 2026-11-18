@extends('layouts.dashboard')
@php($pageTitle = 'Dokumen Transfer Stok')

@section('mobile-content')
<main class="w-full space-y-4 p-4 pb-8">
    <a href="{{ route('inventory.transfers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-[#DC143C]"><span aria-hidden="true">&larr;</span> Daftar distribusi</a>
    @if(session('status'))<p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>@endif

    <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-[#202B3C] px-4 py-5 text-white">
            <div class="flex items-center justify-between gap-3"><p class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/65">Technolife <span class="mx-1 text-white/30">/</span> Logistik</p><span class="rounded-full border border-white/20 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-white/80">Dokumen internal</span></div>
            <h1 class="mt-4 font-poppins text-xl font-semibold tracking-tight">Perintah transfer stok</h1>
            <p class="mt-1.5 max-w-md text-xs leading-relaxed text-white/70">Dokumen pengiriman barang dari gudang ke outlet. Stok baru berkurang setelah persetujuan PIC Gudang.</p>
        </div>
        <div class="grid grid-cols-2 divide-x divide-slate-100 px-4 py-3 text-[10px]"><div class="pr-3"><p class="font-semibold uppercase tracking-wide text-slate-400">Jenis dokumen</p><p class="mt-1 font-semibold text-slate-800">Transfer keluar</p></div><div class="pl-3"><p class="font-semibold uppercase tracking-wide text-slate-400">Tahap</p><p class="mt-1 font-semibold text-slate-700">Siap diajukan</p></div></div>
    </header>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800"><p class="font-semibold">Dokumen belum dapat diajukan</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form data-transfer-form data-confirm-title="Ajukan transfer stok?" data-confirm-message="Stok akan dicek sebelum pengajuan dibuat dan baru berkurang setelah disetujui PIC Gudang." data-confirm-button="Ya, ajukan transfer" method="POST" action="{{ route('inventory.transfers.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">@csrf
        @include('inventory.transfers._fields')
    </form>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('inventory.transfers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-[#DC143C]"><span aria-hidden="true">&larr;</span> Daftar distribusi</a>
    @if(session('status'))<p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</p>@endif

    <header class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-6 border-b border-slate-100 bg-[#202B3C] px-7 py-7 text-white">
            <div><p class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/60">Technolife <span class="mx-1 text-white/30">/</span> Logistik &amp; Persediaan</p><h1 class="mt-4 font-poppins text-3xl font-semibold tracking-tight">Perintah transfer stok</h1><p class="mt-2 max-w-xl text-sm leading-relaxed text-white/70">Dokumen pengiriman barang dari gudang ke outlet. Stok baru berkurang setelah PIC Gudang menyetujui transfer.</p></div>
            <div class="rounded-xl border border-white/20 px-4 py-3"><p class="text-[9px] font-bold uppercase tracking-[0.16em] text-white/55">Status dokumen</p><p class="mt-1.5 flex items-center gap-2 text-sm font-semibold"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Draf pengajuan</p></div>
        </div>
        <div class="grid grid-cols-3 divide-x divide-slate-100 px-7 py-4 text-xs"><div class="pr-5"><p class="font-semibold uppercase tracking-[0.12em] text-slate-400">Jenis dokumen</p><p class="mt-1.5 font-semibold text-slate-800">Transfer keluar</p></div><div class="px-5"><p class="font-semibold uppercase tracking-[0.12em] text-slate-400">Alokasi barang</p><p class="mt-1.5 font-semibold text-slate-800">FEFO otomatis</p></div><div class="pl-5"><p class="font-semibold uppercase tracking-[0.12em] text-slate-400">Dampak stok</p><p class="mt-1.5 font-semibold text-slate-800">Setelah disetujui</p></div></div>
    </header>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-semibold">Dokumen belum dapat diajukan</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form data-transfer-form data-confirm-title="Ajukan transfer stok?" data-confirm-message="Stok akan dicek sebelum pengajuan dibuat dan baru berkurang setelah disetujui PIC Gudang." data-confirm-button="Ya, ajukan transfer" method="POST" action="{{ route('inventory.transfers.store') }}" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">@csrf
        @include('inventory.transfers._fields')
    </form>
</section>
@endsection

@section('page-scripts')
    const products = @json($productOptions);
    const requestedItems = @json($requestItems);
    const stockAvailability = @json($stockAvailability);
    const sourceNames = @json($warehouses->mapWithKeys(fn ($location) => [$location->id => $location->name]));
    const formatQuantity = (quantity) => Number(quantity || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);

    document.querySelectorAll('[data-transfer-items]').forEach((target) => {
        const form = target.closest('form');
        const getSourceId = () => form.querySelector('[data-source-location]')?.value || form.querySelector('[data-fixed-source]')?.value || '';
        const updateAvailability = (row) => {
            const productSelect = row.querySelector('[data-transfer-product]');
            const hint = row.querySelector('[data-stock-hint]');
            const locationId = getSourceId();
            const productId = productSelect.value;

            if (!productId || !locationId) {
                hint.textContent = productId ? 'Pilih gudang asal untuk melihat stok tersedia.' : 'Pilih barang untuk melihat stok tersedia.';
                hint.className = 'mt-1.5 text-[11px] text-slate-500';
                return;
            }

            const product = products.find((entry) => String(entry.id) === String(productId));
            const available = Number(stockAvailability?.[productId]?.[locationId] || 0);
            const warehouse = sourceNames[locationId] || form.querySelector('[data-fixed-source-wrapper]')?.innerText.trim() || 'gudang ini';
            hint.textContent = `Stok tersedia di ${warehouse}: ${formatQuantity(available)} ${product?.unit || 'unit'}`;
            hint.className = `mt-1.5 text-[11px] font-medium ${available > 0 ? 'text-emerald-700' : 'text-rose-700'}`;
        };

        const addRow = (initial = null) => {
            const rowId = 'transfer-item-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            const row = document.createElement('div');
            row.dataset.transferItem = '';
            row.className = 'grid grid-cols-1 items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 sm:grid-cols-[minmax(0,1fr)_150px_auto] sm:items-center';
            const options = products.map((product) => `<option value="${escapeHtml(product.id)}">${escapeHtml(product.sku)} - ${escapeHtml(product.name)} (${escapeHtml(product.unit)})</option>`).join('');
            row.innerHTML = `<div class="min-w-0"><select required data-transfer-product name="items[${rowId}][product_id]" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><option value="">Pilih produk</option>${options}</select><p data-stock-hint class="mt-1.5 text-[11px] text-slate-500">Pilih barang untuk melihat stok tersedia.</p></div><div><label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Jumlah kirim</label><input required type="number" min="0.001" step="0.001" name="items[${rowId}][quantity]" placeholder="Jumlah" class="mt-1 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><p class="mt-1 text-[10px] text-slate-400">Cek stok sebelum ajukan</p></div><button type="button" class="justify-self-end rounded-lg px-2 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 sm:mt-5">Hapus</button>`;
            if (initial) {
                row.querySelector('[data-transfer-product]').value = initial.product_id;
                row.querySelector('input[type="number"]').value = initial.quantity;
            }
            row.querySelector('[data-transfer-product]').addEventListener('change', () => updateAvailability(row));
            row.querySelector('button').addEventListener('click', () => row.remove());
            target.appendChild(row);
            updateAvailability(row);
        };

        form.querySelector('[data-transfer-add]').addEventListener('click', addRow);
        form.querySelectorAll('[data-source-location]').forEach((select) => select.addEventListener('change', () => target.querySelectorAll('[data-transfer-item]').forEach((row) => updateAvailability(row))));
        if (requestedItems.length) requestedItems.forEach((item) => addRow(item));
        else addRow();
    });
@endsection
