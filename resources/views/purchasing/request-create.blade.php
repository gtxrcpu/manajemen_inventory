@extends('layouts.dashboard')

@php
    $isEditingRequest = (bool) $purchaseRequest;
    $requestAction = $isEditingRequest ? route('purchasing.requests.update', $purchaseRequest) : route('purchasing.requests.store');
@endphp

@section('mobile-content')
<main class="mx-auto w-full max-w-3xl space-y-4 p-4">
    <a href="{{ $isEditingRequest ? route('purchasing.requests.show', $purchaseRequest) : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600"><span class="material-symbols-outlined text-[18px]">arrow_back</span>{{ $isEditingRequest ? 'Kembali ke dokumen PR' : 'Kembali ke pengadaan' }}</a>
    <header><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">Dokumen pengadaan</p><h1 class="mt-1 font-poppins text-xl font-semibold text-slate-950">{{ $isEditingRequest ? 'Ubah Purchase Request' : 'Buat Purchase Request' }}</h1><p class="mt-1 text-sm leading-relaxed text-slate-500">Isi tujuan, alasan, kondisi stok, dan estimasi biaya untuk bahan review Management.</p></header>
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $requestAction }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" data-pr-form="m" data-confirm-title="{{ $isEditingRequest ? 'Simpan perubahan PR?' : 'Kirim Purchase Request?' }}" data-confirm-message="{{ $isEditingRequest ? 'Perubahan akan tersimpan dan PR tetap menunggu persetujuan Management.' : 'PR akan dikirim ke Management untuk ditinjau sebelum PO dapat diterbitkan.' }}" data-confirm-button="{{ $isEditingRequest ? 'Simpan perubahan' : 'Ya, kirim PR' }}">
        @csrf @if($isEditingRequest) @method('PUT') @endif
        @include('purchasing._request-fields', ['suffix' => 'm'])
    </form>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <a href="{{ $isEditingRequest ? route('purchasing.requests.show', $purchaseRequest) : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-[#DC143C]"><span class="material-symbols-outlined text-[18px]">arrow_back</span>{{ $isEditingRequest ? 'Kembali ke dokumen PR' : 'Kembali ke pengadaan' }}</a>
    <header><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#DC143C]">Dokumen pengadaan</p><h1 class="mt-1 font-poppins text-2xl font-semibold text-slate-950">{{ $isEditingRequest ? 'Ubah Purchase Request' : 'Buat Purchase Request' }}</h1><p class="mt-1 text-sm text-slate-500">{{ $isEditingRequest ? 'Perbarui detail kebutuhan sebelum Management mengambil keputusan.' : 'Lengkapi konteks kebutuhan agar Management dapat menilai permintaan dengan tepat.' }}</p></header>
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $requestAction }}" class="space-y-6 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-pr-form="d" data-confirm-title="{{ $isEditingRequest ? 'Simpan perubahan PR?' : 'Kirim Purchase Request?' }}" data-confirm-message="{{ $isEditingRequest ? 'Perubahan akan tersimpan dan PR tetap menunggu persetujuan Management.' : 'PR akan dikirim ke Management untuk ditinjau sebelum PO dapat diterbitkan.' }}" data-confirm-button="{{ $isEditingRequest ? 'Simpan perubahan' : 'Ya, kirim PR' }}">
        @csrf @if($isEditingRequest) @method('PUT') @endif
        @include('purchasing._request-fields', ['suffix' => 'd'])
    </form>
</section>
@endsection

@section('page-scripts')
const prProducts = @json($productOptions);
const initialPrItems = @json($initialItems);
const prSeedItems = Array.isArray(initialPrItems) ? initialPrItems : Object.values(initialPrItems || {});
const prEstimateUrl = @json(route('purchasing.requests.estimate'));
const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 });
const quantityFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 });

function refreshPrTotal(container) {
    let total = 0;
    container.querySelectorAll('[data-pr-subtotal-value]').forEach(input => total += Number(input.value || 0));
    const output = container.closest('form')?.querySelector('[data-pr-total]');
    if (output) output.textContent = currencyFormatter.format(total);
}

function refreshPrSubtotal(row) {
    const quantity = Number(row.querySelector('[data-pr-quantity]').value || 0);
    const price = Number(row.querySelector('[data-pr-price]').value || 0);
    const subtotal = Math.round(quantity * price * 100) / 100;
    row.querySelector('[data-pr-subtotal-value]').value = subtotal;
    row.querySelector('[data-pr-subtotal]').textContent = currencyFormatter.format(subtotal);
    refreshPrTotal(row.closest('[data-pr-rows]'));
}

async function loadPrEstimate(row, selectedProductId, allowPriceAutofill = true) {
    const locationId = row.closest('form').querySelector('[data-pr-location]').value;
    const requestToken = String((Number(row.dataset.estimateToken || 0) + 1));
    row.dataset.estimateToken = requestToken;
    const product = prProducts.find(item => String(item.id) === String(selectedProductId));
    const stock = row.querySelector('[data-pr-stock]');
    const state = row.querySelector('[data-pr-stock-state]');
    if (!locationId || !product) {
        stock.textContent = 'Pilih lokasi dan barang';
        state.textContent = 'Snapshot stok dihitung saat PR disimpan';
        return;
    }
    stock.textContent = 'Memuat stok…';
    state.textContent = 'Mengambil data lokasi';
    try {
        const url = new URL(prEstimateUrl, window.location.origin);
        url.searchParams.set('product_id', selectedProductId);
        url.searchParams.set('location_id', locationId);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('estimate');
        const estimate = await response.json();
        if (row.dataset.estimateToken !== requestToken || row.closest('form').querySelector('[data-pr-location]').value !== locationId || productSelectValue(row) !== String(selectedProductId)) return;
        stock.textContent = `Saldo sistem: ${quantityFormatter.format(estimate.stock_snapshot)} ${product.unit}`;
        const available = estimate.available_stock ?? estimate.stock_snapshot;
        if (estimate.minimum_stock !== null) {
            state.textContent = estimate.below_minimum
                ? `Tersedia ${quantityFormatter.format(available)} ${product.unit} · di bawah minimum ${quantityFormatter.format(estimate.minimum_stock)} ${product.unit}`
                : `Tersedia ${quantityFormatter.format(available)} ${product.unit} · minimum ${quantityFormatter.format(estimate.minimum_stock)} ${product.unit}`;
            state.className = estimate.below_minimum ? 'mt-1 block text-[11px] font-semibold text-rose-700' : 'mt-1 block text-[11px] text-slate-500';
        } else {
            state.textContent = `Tersedia ${quantityFormatter.format(available)} ${product.unit} · batas minimum belum diatur`;
            state.className = 'mt-1 block text-[11px] text-slate-500';
        }
        const priceInput = row.querySelector('[data-pr-price]');
        if (allowPriceAutofill && !priceInput.dataset.userEdited && estimate.estimated_unit_price !== null) {
            priceInput.value = estimate.estimated_unit_price;
            priceInput.placeholder = 'Harga pembelian terakhir';
            refreshPrSubtotal(row);
        } else if (estimate.estimated_unit_price === null) {
            priceInput.placeholder = 'Belum ada riwayat harga';
        }
    } catch (error) {
        if (row.dataset.estimateToken !== requestToken) return;
        stock.textContent = 'Stok tidak dapat dimuat';
        state.textContent = 'Nilai snapshot tetap akan dihitung server saat PR dikirim';
        state.className = 'mt-1 block text-[11px] text-amber-700';
    }
}

function productSelectValue(row) {
    return String(row.querySelector('[data-pr-product]').value || '');
}

function addPrRow(container, suffix, initial = {}) {
    const rowId = `pr-${suffix}-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
    const row = document.createElement('section');
    row.className = 'rounded-xl border border-slate-200 bg-white p-3 sm:p-4';
    row.dataset.prRow = '';
    row.innerHTML = `<div class="mb-3 flex items-start justify-between gap-2"><div><span data-pr-stock class="text-sm font-semibold text-slate-800">Pilih lokasi dan barang</span><span data-pr-stock-state class="mt-1 block text-[11px] text-slate-500">Snapshot stok dihitung saat PR disimpan</span></div><button type="button" data-pr-remove class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button></div><div class="grid gap-3 sm:grid-cols-2"><label class="text-xs font-semibold text-slate-700 sm:col-span-2">Barang <span class="text-rose-600">*</span><select required data-pr-product name="items[${rowId}][product_id]" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Pilih produk</option></select></label><label class="text-xs font-semibold text-slate-700">Jumlah diminta <span class="text-rose-600">*</span><input required type="number" min="0.001" step="0.001" data-pr-quantity name="items[${rowId}][quantity]" placeholder="Contoh: 10" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></label><label class="text-xs font-semibold text-slate-700">Estimasi harga satuan <span class="text-rose-600">*</span><input required type="number" min="0" step="0.01" data-pr-price name="items[${rowId}][estimated_unit_price]" placeholder="Mengambil harga terakhir" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><span class="mt-1 block text-[11px] font-normal text-slate-500">Dapat disesuaikan oleh pemohon</span></label><label class="text-xs font-semibold text-slate-700 sm:col-span-2">Spesifikasi / catatan item <span class="font-normal text-slate-500">(opsional)</span><input type="text" maxlength="1000" data-pr-item-notes name="items[${rowId}][notes]" placeholder="Contoh: ukuran, merek, atau kondisi khusus" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></label></div><div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs"><span class="text-slate-500">Estimasi subtotal</span><strong data-pr-subtotal class="text-sm font-bold tabular-nums text-slate-900">Rp0</strong><input type="hidden" data-pr-subtotal-value value="0"></div>`;
    const productSelect = row.querySelector('[data-pr-product]');
    prProducts.forEach(product => {
        const option = document.createElement('option');
        option.value = product.id;
        option.textContent = `${product.sku} — ${product.name} (${product.unit})`;
        productSelect.appendChild(option);
    });
    productSelect.value = initial.product_id ?? '';
    row.querySelector('[data-pr-quantity]').value = initial.quantity ?? '';
    row.querySelector('[data-pr-price]').value = initial.estimated_unit_price ?? '';
    row.querySelector('[data-pr-item-notes]').value = initial.notes ?? '';
    if (initial.estimated_unit_price !== null && initial.estimated_unit_price !== undefined && initial.estimated_unit_price !== '') row.querySelector('[data-pr-price]').dataset.userEdited = 'true';
    row.querySelector('[data-pr-remove]').addEventListener('click', () => { row.remove(); refreshPrTotal(container); });
    productSelect.addEventListener('change', () => { const priceInput = row.querySelector('[data-pr-price]'); priceInput.dataset.userEdited = ''; priceInput.value = ''; priceInput.placeholder = 'Mengambil harga terakhir'; refreshPrSubtotal(row); loadPrEstimate(row, productSelect.value, true); });
    row.querySelector('[data-pr-quantity]').addEventListener('input', () => refreshPrSubtotal(row));
    row.querySelector('[data-pr-price]').addEventListener('input', event => { event.currentTarget.dataset.userEdited = 'true'; refreshPrSubtotal(row); });
    container.appendChild(row);
    refreshPrSubtotal(row);
    if (productSelect.value) loadPrEstimate(row, productSelect.value, !row.querySelector('[data-pr-price]').dataset.userEdited);
}

document.querySelectorAll('[data-pr-rows]').forEach(container => {
    const suffix = container.dataset.suffix;
    if (prSeedItems.length) prSeedItems.forEach(item => addPrRow(container, suffix, item));
    else addPrRow(container, suffix);
    const form = container.closest('form');
    form.querySelector('[data-pr-location]').addEventListener('change', () => {
        container.querySelectorAll('[data-pr-row]').forEach(row => loadPrEstimate(row, row.querySelector('[data-pr-product]').value, false));
    });
});
document.querySelectorAll('[data-pr-add]').forEach(button => button.addEventListener('click', () => {
    const container = document.getElementById(button.dataset.target);
    addPrRow(container, container.dataset.suffix);
}));
@endsection
