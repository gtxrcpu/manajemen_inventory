@extends('layouts.dashboard')
@php($pageTitle = 'Catat Stock In — Technolife')
@section('mobile-content')
<main class="w-full p-4"><a href="{{ route('inventory.stock-in.index') }}" class="text-sm font-semibold text-primary">← Kembali ke Stock In</a><h1 class="mt-3 font-poppins text-xl font-semibold">Catat barang masuk</h1><p class="mt-1 text-xs text-secondary">Saldo bertambah setelah penerimaan dikonfirmasi.</p>
@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form id="stock-in-form-m" data-stock-in-form data-confirm-title="Konfirmasi barang masuk?" data-confirm-message="Setelah disimpan, jumlah yang diterima akan langsung menambah saldo stok. Pastikan tanggal dan jumlahnya sudah benar." data-confirm-button="Ya, catat barang masuk" method="POST" action="{{ route('inventory.stock-in.store') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf
@include('inventory.stock-in._fields', ['suffix'=>'m'])
</form></main>
@endsection
@section('desktop-content')
<section class="mx-auto max-w-4xl space-y-5"><a href="{{ route('inventory.stock-in.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Stock In</a><div><h1 class="font-poppins text-2xl font-semibold">Catat barang masuk</h1><p class="mt-1 text-sm text-gray-500">Saldo bertambah setelah penerimaan dikonfirmasi.</p></div>
@if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form id="stock-in-form-d" data-stock-in-form data-confirm-title="Konfirmasi barang masuk?" data-confirm-message="Setelah disimpan, jumlah yang diterima akan langsung menambah saldo stok. Pastikan tanggal dan jumlahnya sudah benar." data-confirm-button="Ya, catat barang masuk" method="POST" action="{{ route('inventory.stock-in.store') }}" class="space-y-5 rounded-2xl border border-[#EAEAEA] bg-white p-6">@csrf
@include('inventory.stock-in._fields', ['suffix'=>'d'])
</form></section>
@endsection
@section('page-scripts')
    const products = @json($productOptions);
    function addStockInRow(targetId, suffix) {
        const target = document.getElementById(targetId);
        const rowId = 'stock-in-row-' + suffix + '-' + Date.now() + '-' + Math.floor(Math.random()*1000);
        const options = products.map(product => `<option value="${product.id}">${product.sku} — ${product.name} (${product.unit})</option>`).join('');
        const row = document.createElement('div'); row.id = rowId;
        row.className = 'grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 sm:grid-cols-2 xl:grid-cols-[1.3fr_120px_1fr_1fr_auto]';
        row.innerHTML = `
            <label class="block text-xs font-medium text-gray-600">Produk <select required name="items[${rowId}][product_id]" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm"><option value="">Pilih produk</option>${options}</select></label>
            <label class="block text-xs font-medium text-gray-600">Jumlah <input required type="number" min="0.001" step="0.001" name="items[${rowId}][quantity]" placeholder="Jumlah" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
            <label class="block text-xs font-medium text-gray-600">Nomor batch (opsional) <input name="items[${rowId}][batch_number]" maxlength="100" placeholder="Isi jika tercantum" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
            <label class="block text-xs font-medium text-gray-600">Tanggal kedaluwarsa (opsional) <input type="date" name="items[${rowId}][expiry_date]" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm"><span class="mt-1 block font-normal text-gray-500">Pilih tanggal yang tercetak di kemasan. Kosongkan jika tidak ada.</span></label>
            <button type="button" data-remove-stock-in class="self-end py-2 text-sm font-semibold text-red-600">Hapus</button>`;
        row.querySelector('[data-remove-stock-in]').addEventListener('click', () => row.remove());
        target.appendChild(row);
    }
    document.querySelectorAll('[data-stock-in-add]').forEach(button => button.addEventListener('click', () => addStockInRow(button.dataset.target, button.dataset.suffix)));
    document.querySelectorAll('[data-stock-in-rows]').forEach(el => addStockInRow(el.id, el.dataset.suffix));
@endsection
