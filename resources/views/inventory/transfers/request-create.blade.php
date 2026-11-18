@extends('layouts.dashboard')
@php($pageTitle = 'Permintaan Stok Outlet')

@section('mobile-content')
<main class="w-full space-y-4 p-4"><a href="{{ route('inventory.transfers.index') }}" class="text-sm font-semibold text-primary">← Kembali ke distribusi</a><h1 class="font-poppins text-xl font-semibold">Minta stok ke gudang</h1><p class="text-sm text-secondary">Permintaan diteruskan ke tim gudang untuk disiapkan.</p>
    @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form data-transfer-request method="POST" action="{{ route('inventory.transfers.requests.store') }}" class="space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf
        @if($locations->count() > 1)<label class="block text-sm font-semibold">Outlet tujuan<select name="location_id" required class="mt-1 w-full rounded-lg border p-2.5">@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) $selectedLocationId === (string) $location->id)>{{ $location->name }}</option>@endforeach</select></label>@endif
        <div class="flex items-center justify-between"><b>Daftar barang</b><button type="button" data-request-add class="rounded-lg border px-3 py-2 text-xs font-semibold">+ Tambah barang</button></div><div data-request-items class="space-y-2"></div>
        <label class="block text-sm font-semibold">Catatan<textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border p-2.5 text-sm">{{ old('notes') }}</textarea></label><button class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white">Kirim permintaan</button>
    </form>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-3xl space-y-5"><a href="{{ route('inventory.transfers.index') }}" class="text-sm font-semibold text-primary">← Kembali ke distribusi</a><h1 class="font-poppins text-2xl font-semibold">Minta stok ke gudang</h1><p class="text-sm text-gray-500">Permintaan diteruskan ke tim gudang untuk disiapkan.</p>
    @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form data-transfer-request method="POST" action="{{ route('inventory.transfers.requests.store') }}" class="space-y-5 rounded-2xl border bg-white p-6">@csrf
        @if($locations->count() > 1)<label class="block text-sm font-semibold">Outlet tujuan<select name="location_id" required class="mt-1 w-full rounded-lg border p-2.5">@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) $selectedLocationId === (string) $location->id)>{{ $location->name }}</option>@endforeach</select></label>@endif
        <div class="flex items-center justify-between"><b>Daftar barang</b><button type="button" data-request-add class="rounded-lg border px-3 py-2 text-sm font-semibold">+ Tambah barang</button></div><div data-request-items class="space-y-2"></div>
        <label class="block text-sm font-semibold">Catatan<textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border p-2.5 text-sm">{{ old('notes') }}</textarea></label><button class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white">Kirim permintaan</button>
    </form>
</section>
@endsection

@section('page-scripts')
    const products = @json($productOptions);
    document.querySelectorAll('[data-request-items]').forEach(target => {
        let rowIndex = 0;
        const addRow = () => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 gap-2 rounded-xl border border-gray-200 p-3 sm:grid-cols-[1fr_140px_auto]';
            const options = products.map(product => `<option value="${product.id}">${product.sku} — ${product.name} (${product.unit})</option>`).join('');
            row.innerHTML = `<select required name="items[${rowIndex}][product_id]" class="rounded-lg border border-gray-300 p-2.5 text-sm"><option value="">Pilih produk</option>${options}</select><input required type="number" min="0.001" step="0.001" name="items[${rowIndex}][quantity]" placeholder="Jumlah" class="rounded-lg border border-gray-300 p-2.5 text-sm"><button type="button" class="text-sm font-semibold text-red-600">Hapus</button>`;
            row.querySelector('button').addEventListener('click', () => row.remove()); target.appendChild(row); rowIndex++;
        };
        target.closest('form').querySelector('[data-request-add]').addEventListener('click', addRow); addRow();
    });
@endsection
