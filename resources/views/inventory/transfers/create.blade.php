@extends('layouts.dashboard')
@php($pageTitle = 'Buat Transfer Stok')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a href="{{ route('inventory.transfers.index') }}" class="text-sm font-semibold text-primary">← Kembali ke distribusi</a>
    @if(session('status'))<p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>@endif
    <div><h1 class="font-poppins text-xl font-semibold">Kirim stok ke outlet</h1><p class="mt-1 text-xs text-secondary">Stok gudang berkurang saat dikirim. PIC Outlet mencatat penerimaan agar saldo outlet bertambah.</p></div>
    @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form data-transfer-form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf
        @include('inventory.transfers._fields')
    </form>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-4xl space-y-5">
    <a href="{{ route('inventory.transfers.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke distribusi</a>
    @if(session('status'))<p class="rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>@endif
    <div><h1 class="font-poppins text-2xl font-semibold">Kirim stok ke outlet</h1><p class="mt-1 text-sm text-gray-500">Alokasi batch di gudang mengikuti FEFO. Outlet menerima seluruh jumlah transfer dalam satu langkah.</p></div>
    @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form data-transfer-form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-5 rounded-2xl border border-[#EAEAEA] bg-white p-6">@csrf
        @include('inventory.transfers._fields')
    </form>
</section>
@endsection

@section('page-scripts')
    const products = @json($productOptions);
    const requestedItems = @json($requestItems);
    document.querySelectorAll('[data-transfer-items]').forEach((target) => {
        const form = target.closest('form');
        const addRow = (initial = null) => {
            const rowId = 'transfer-item-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 gap-2 rounded-xl border border-gray-200 p-3 sm:grid-cols-[1fr_140px_auto]';
            const options = products.map(product => `<option value="${product.id}">${product.sku} — ${product.name} (${product.unit})</option>`).join('');
            row.innerHTML = `<select required name="items[${rowId}][product_id]" class="rounded-lg border border-gray-300 p-2.5 text-sm"><option value="">Pilih produk</option>${options}</select><input required type="number" min="0.001" step="0.001" name="items[${rowId}][quantity]" placeholder="Jumlah" class="rounded-lg border border-gray-300 p-2.5 text-sm"><button type="button" class="text-sm font-semibold text-red-600">Hapus</button>`;
            if (initial) { row.querySelector('select').value = initial.product_id; row.querySelector('input').value = initial.quantity; }
            row.querySelector('button').addEventListener('click', () => row.remove());
            target.appendChild(row);
        };
        target.closest('form').querySelector('[data-transfer-add]').addEventListener('click', addRow);
        if (requestedItems.length) requestedItems.forEach(item => addRow(item)); else addRow();
    });
    document.querySelectorAll('[data-transfer-form]').forEach(form => form.addEventListener('submit', event => {
        if (!confirm('Kirim transfer ini? Stok gudang akan langsung berkurang.')) event.preventDefault();
    }));
@endsection
