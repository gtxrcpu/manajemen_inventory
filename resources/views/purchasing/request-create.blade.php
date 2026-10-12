@extends('layouts.dashboard')
@php($pageTitle = 'Buat Purchase Request — Technolife')
@section('mobile-content')
<main class="w-full p-4"><a href="{{ route('purchasing.index') }}" class="text-sm font-semibold text-primary">← Kembali ke Pengadaan</a><h1 class="mt-3 font-poppins text-xl font-semibold">Buat Purchase Request</h1><p class="mt-1 text-xs text-secondary">Permintaan akan dikirim untuk persetujuan Super Admin.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif<form method="POST" action="{{ route('purchasing.requests.store') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4" data-pr-form="m">@csrf @include('purchasing._request-fields', ['suffix' => 'm'])</form></main>
@endsection
@section('desktop-content')
<section class="mx-auto max-w-4xl space-y-5"><a href="{{ route('purchasing.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Pengadaan</a><div><h1 class="font-poppins text-2xl font-semibold">Buat Purchase Request</h1><p class="mt-1 text-sm text-gray-500">Permintaan akan dikirim untuk persetujuan Super Admin.</p></div>@if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif<form method="POST" action="{{ route('purchasing.requests.store') }}" class="space-y-5 rounded-2xl border border-[#EAEAEA] bg-white p-6" data-pr-form="d">@csrf @include('purchasing._request-fields', ['suffix' => 'd'])</form></section>
@endsection
@section('page-scripts')
const prProducts = @json($productOptions);
function addPrRow(target, suffix) { const node = document.getElementById(target); const id = `pr-${suffix}-${Date.now()}-${Math.floor(Math.random()*1000)}`; const options = prProducts.map(p => `<option value="${p.id}">${p.sku} — ${p.name} (${p.unit})</option>`).join(''); const row = document.createElement('div'); row.className = 'grid grid-cols-1 gap-2 rounded-xl border border-gray-200 p-3 sm:grid-cols-[1fr_150px_auto]'; row.innerHTML = `<select required name="items[${id}][product_id]" class="rounded-lg border p-2.5 text-sm"><option value="">Pilih produk</option>${options}</select><input required type="number" min="0.001" step="0.001" name="items[${id}][quantity]" placeholder="Jumlah" class="rounded-lg border p-2.5 text-sm"><button type="button" class="text-sm font-semibold text-red-600">Hapus</button>`; row.querySelector('button').onclick = () => row.remove(); node.appendChild(row); }
document.querySelectorAll('[data-pr-rows]').forEach(el => addPrRow(el.id, el.dataset.suffix));
document.querySelectorAll('[data-pr-add]').forEach(el => el.addEventListener('click', () => addPrRow(el.dataset.target, el.dataset.suffix)));
document.querySelectorAll('[data-pr-form]').forEach(el => el.addEventListener('submit', e => { if (!confirm('Kirim Purchase Request untuk persetujuan?')) e.preventDefault(); }));
@endsection
