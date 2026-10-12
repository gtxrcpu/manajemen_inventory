@extends('layouts.dashboard')
@php($pageTitle = 'Catat Stock Out — Technolife')
@section('mobile-content')<main class="w-full p-4"><a href="{{ route('inventory.stock-out.index') }}" class="text-sm font-semibold text-primary">← Kembali ke Stock Out</a><h1 class="mt-3 font-poppins text-xl font-semibold">Catat barang keluar</h1><p class="mt-1 text-xs text-secondary">Jumlah tidak bisa melebihi saldo di lokasi asal.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="stock-out-form-m" data-stock-out-form method="POST" action="{{ route('inventory.stock-out.store') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf @include('inventory.stock-out._fields',['suffix'=>'m'])</form></main>@endsection
@section('desktop-content')<section class="mx-auto max-w-4xl space-y-5"><a href="{{ route('inventory.stock-out.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Stock Out</a><div><h1 class="font-poppins text-2xl font-semibold">Catat barang keluar</h1><p class="mt-1 text-sm text-gray-500">Jumlah tidak bisa melebihi saldo di lokasi asal.</p></div>@if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="stock-out-form-d" data-stock-out-form method="POST" action="{{ route('inventory.stock-out.store') }}" class="space-y-5 rounded-2xl border border-[#EAEAEA] bg-white p-6">@csrf @include('inventory.stock-out._fields',['suffix'=>'d'])</form></section>@endsection
@section('page-scripts')
    const products = @json($productOptions);
    const batchOptions = @json($batchOptions);
    function addStockOutRow(targetId, suffix) {
        const target = document.getElementById(targetId);
        const form = target.closest('form');
        const rowId = 'stock-out-row-' + suffix + '-' + Date.now() + '-' + Math.floor(Math.random()*1000);
        const row = document.createElement('div'); row.id = rowId;
        row.className = 'grid grid-cols-1 gap-2 rounded-xl border border-gray-200 p-3 sm:grid-cols-[1fr_140px_auto]';
        const select = document.createElement('select'); select.required = true; select.name = `items[${rowId}][product_id]`; select.className = 'rounded-lg border border-gray-300 p-2.5 text-sm';
        select.add(new Option('Pilih produk', ''));
        products.forEach(product => select.add(new Option(`${product.sku} — ${product.name} (${product.unit})`, product.id)));
        const quantity = document.createElement('input'); quantity.required = true; quantity.type = 'number'; quantity.min = '0.001'; quantity.step = '0.001'; quantity.name = `items[${rowId}][quantity]`; quantity.placeholder = 'Jumlah'; quantity.className = 'rounded-lg border border-gray-300 p-2.5 text-sm';
        const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Hapus'; remove.className = 'text-sm font-semibold text-red-600'; remove.addEventListener('click', () => row.remove());
        const preview = document.createElement('p'); preview.className = 'sm:col-span-3 text-xs text-secondary';
        const refreshPreview = () => {
            const locationId = form.querySelector('[name=location_id]').value;
            const issuedAt = form.querySelector('[name=issued_at]').value;
            const choices = (batchOptions[select.value]?.[locationId] || []).filter(choice => !choice.expiry_date || !issuedAt || choice.expiry_date >= issuedAt);
            let remaining = Number(quantity.value || 0);
            const plan = [];
            for (const choice of choices) {
                if (remaining <= 0.0005) break;
                const allocated = Math.min(remaining, Number(choice.quantity));
                if (allocated > 0) {
                    plan.push(`${choice.label}: ${allocated.toLocaleString('id-ID', { maximumFractionDigits: 3 })}`);
                    remaining -= allocated;
                }
            }
            preview.textContent = plan.length ? `Alokasi FEFO: ${plan.join(' → ')}${remaining > 0.0005 ? ` · Kurang ${remaining.toLocaleString('id-ID', { maximumFractionDigits: 3 })}` : ''}` : 'Pilih produk dan jumlah untuk melihat alokasi FEFO.';
        };
        select.addEventListener('change', refreshPreview);
        quantity.addEventListener('input', refreshPreview);
        form.querySelector('[name=location_id]').addEventListener('change', refreshPreview);
        form.querySelector('[name=issued_at]').addEventListener('change', refreshPreview);
        row.append(select, quantity, remove, preview); target.appendChild(row);
        refreshPreview();
    }
    document.querySelectorAll('[data-stock-out-add]').forEach(button => button.addEventListener('click', () => addStockOutRow(button.dataset.target, button.dataset.suffix)));
    document.querySelectorAll('[data-stock-out-form]').forEach(form => form.addEventListener('submit', event => { if (!confirm('Konfirmasi barang keluar? Stok akan dialokasikan otomatis dengan FEFO dan saldo langsung berkurang.')) event.preventDefault(); }));
    document.querySelectorAll('[data-stock-out-rows]').forEach(el => addStockOutRow(el.id, el.dataset.suffix));
@endsection
