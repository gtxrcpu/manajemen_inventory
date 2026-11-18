@extends('layouts.dashboard')
@php($pageTitle = 'Buat Stock Opname — Technolife')
@section('mobile-content')<main class="w-full p-4"><a href="{{ route('inventory.stock-opname.index') }}" class="text-sm font-semibold text-primary">← Kembali</a><h1 class="mt-3 font-poppins text-xl font-semibold">Buat sesi Stock Opname</h1><p class="mt-1 text-xs text-secondary">Cycle count mingguan menghitung produk terpilih; akhir bulan menghitung semua produk.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif<form method="POST" data-opname-start-form action="{{ route('inventory.stock-opname.start') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf
@include('inventory.stock-opname._start-fields')
</form></main>@endsection
@section('desktop-content')<section class="mx-auto max-w-2xl"><a href="{{ route('inventory.stock-opname.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Stock Opname</a><div class="mt-4 rounded-2xl border border-[#EAEAEA] bg-white p-6"><h1 class="font-poppins text-2xl font-semibold">Buat sesi Stock Opname</h1><p class="mt-1 text-sm text-gray-500">Cycle count mingguan menghitung produk terpilih; akhir bulan menghitung semua produk.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif<form method="POST" data-opname-start-form action="{{ route('inventory.stock-opname.start') }}" class="mt-5 space-y-4">@csrf @include('inventory.stock-opname._start-fields')</form></div></section>@endsection
@section('page-scripts')
document.querySelectorAll('[data-opname-start-form]').forEach(form => {
    const period = form.querySelector('[name="period_type"]');
    const scopes = form.querySelector('[data-weekly-scope]');
    const products = form.querySelector('[data-weekly-products]');
    const monthEndNote = form.querySelector('[data-month-end-note]');
    const update = () => {
        const weekly = period.value === 'weekly';
        const partial = form.querySelector('[name="count_scope"]:checked')?.value === 'partial';
        scopes.hidden = !weekly;
        products.hidden = !weekly || !partial;
        products.querySelectorAll('input[type="checkbox"]').forEach(input => input.disabled = !weekly || !partial);
        form.querySelectorAll('[name="count_scope"]').forEach(input => input.disabled = !weekly);
        monthEndNote.hidden = weekly;
    };
    period.addEventListener('change', update);
    form.querySelectorAll('[name="count_scope"]').forEach(input => input.addEventListener('change', update));
    form.querySelector('[data-product-search]').addEventListener('input', event => {
        const term = event.target.value.toLocaleLowerCase('id-ID').trim();
        products.querySelectorAll('[data-opname-product]').forEach(row => row.hidden = !row.dataset.search.includes(term));
    });
    update();
});
@endsection
