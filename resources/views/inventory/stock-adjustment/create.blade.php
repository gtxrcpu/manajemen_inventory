@extends('layouts.dashboard')
@php($pageTitle = 'Buat Stock Adjustment — Technolife')
@section('mobile-content')<main class="w-full p-4"><a href="{{ route('inventory.stock-adjustments.index') }}" class="text-sm font-semibold text-primary">← Kembali</a><h1 class="mt-3 font-poppins text-xl font-semibold">Koreksi saldo stok</h1><p class="mt-1 text-xs text-secondary">Penyesuaian langsung dicatat dan tidak dapat dihapus.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif<form id="adjust-form-m" data-adjustment-form data-confirm-title="Simpan koreksi stok?" data-confirm-message="Saldo stok akan langsung berubah dan transaksi penyesuaian ini tidak dapat dihapus. Pastikan arah dan jumlah koreksi sudah benar." data-confirm-button="Ya, simpan koreksi" method="POST" action="{{ route('inventory.stock-adjustments.store') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf @include('inventory.stock-adjustment._fields')</form></main>@endsection
@section('desktop-content')<section class="mx-auto max-w-3xl"><a href="{{ route('inventory.stock-adjustments.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Stock Adjustment</a><div class="mt-4 rounded-2xl border border-[#EAEAEA] bg-white p-6"><h1 class="font-poppins text-2xl font-semibold">Koreksi saldo stok</h1><p class="mt-1 text-sm text-gray-500">Penyesuaian langsung dicatat dan tidak dapat dihapus.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif<form id="adjust-form-d" data-adjustment-form data-confirm-title="Simpan koreksi stok?" data-confirm-message="Saldo stok akan langsung berubah dan transaksi penyesuaian ini tidak dapat dihapus. Pastikan arah dan jumlah koreksi sudah benar." data-confirm-button="Ya, simpan koreksi" method="POST" action="{{ route('inventory.stock-adjustments.store') }}" class="mt-5 space-y-4">@csrf @include('inventory.stock-adjustment._fields')</form></div></section>@endsection
@section('page-scripts')
    const adjustmentProducts = @json($productOptions);
    document.querySelectorAll('[data-adjustment-form]').forEach(form => {
        const product = form.querySelector('[name="product_id"]');
        const location = form.querySelector('[name="location_id"]');
        const direction = form.querySelector('[name="direction"]');
        const quantity = form.querySelector('[name="quantity"]');
        const available = form.querySelector('[data-adjustment-current]');
        const result = form.querySelector('[data-adjustment-result]');
        function update() {
            const selected = adjustmentProducts.find(item => String(item.id) === product.value);
            const current = selected ? Number(selected.balances[location.value] || 0) : 0;
            const amount = Number(quantity.value || 0);
            const next = direction.value === 'increase' ? current + amount : current - amount;
            available.textContent = `${current.toLocaleString('id-ID')} ${selected?.unit || ''}`;
            result.textContent = `${next.toLocaleString('id-ID')} ${selected?.unit || ''}`;
            result.classList.toggle('text-red-600', next < 0);
        }
        [product, location, direction, quantity].forEach(input => input.addEventListener('change', update));
        quantity.addEventListener('input', update); update();
    });
@endsection
