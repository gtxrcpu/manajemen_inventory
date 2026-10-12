@extends('layouts.dashboard')
@php($pageTitle = 'Pencarian')

@section('mobile-content')
<main class="w-full space-y-5 p-4">
    <h1 class="font-poppins text-xl font-semibold">Pencarian</h1>
    @include('search.partials.results')
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <div><h1 class="font-poppins text-2xl font-semibold">Pencarian</h1><p class="mt-1 text-sm text-gray-500">Cari produk, SKU, batch, atau supplier.</p></div>
    @include('search.partials.results')
</section>
@endsection
