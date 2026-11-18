@extends('layouts.dashboard')
@php($pageTitle = 'Atur Batas Stok Minimum')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a href="{{ route('low-stock.index') }}" class="text-sm font-semibold text-primary">← Kembali ke alert</a>
    <div>
        <h1 class="font-poppins text-xl font-semibold">Atur batas minimum</h1>
        <p class="mt-1 text-xs text-secondary">Batas berlaku untuk kombinasi produk dan lokasi. Saldo dan status dihitung dari transaksi lokasi yang dipilih. Isi 0 untuk menonaktifkan alert.</p>
    </div>

    @if(session('status'))<p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>@endif
    @if($errors->any())<p class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif

    <form method="GET" action="{{ route('low-stock.thresholds') }}" class="rounded-xl border border-outline bg-white p-3">
        <label class="text-xs font-semibold">Lokasi
            <select name="location_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border p-2.5 text-sm">
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected($locationId == $location->id)>{{ $location->name }}</option>@endforeach
            </select>
        </label>
    </form>

    @if($locationId)
    <form method="POST" action="{{ route('low-stock.thresholds.save') }}" class="space-y-3">
        @csrf @method('PUT')
        <input type="hidden" name="location_id" value="{{ $locationId }}">
        <input type="hidden" name="page" value="{{ $products->currentPage() }}">
        @forelse($products as $product)
            @php($alertState = $alertStates[$product->id])
            <div class="flex items-center justify-between gap-3 rounded-xl border border-outline bg-white p-3">
                <div class="min-w-0 flex-1">
                    <b class="block truncate text-sm">{{ $product->name }}</b>
                    <small class="text-xs text-secondary">{{ $product->sku }} · {{ $product->unit->symbol }}</small>
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                        <span class="text-gray-600">Saldo: <b>{{ \App\Support\QuantityFormatter::format($alertState['balance']) }} {{ $product->unit->symbol }}</b></span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                            'bg-gray-100 text-gray-600' => $alertState['state'] === 'Nonaktif',
                            'bg-red-50 text-red-700' => $alertState['state'] === 'Stok rendah',
                            'bg-green-50 text-green-700' => $alertState['state'] === 'Aman',
                        ])>{{ $alertState['state'] }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1.5">
                    <input required type="number" name="thresholds[{{ $product->id }}]" min="0" step="0.001" value="{{ \App\Support\QuantityFormatter::formatInput(old('thresholds.'.$product->id, $thresholds[$product->id] ?? '0')) }}" aria-label="Batas minimum {{ $product->name }}" class="w-24 rounded-lg border p-2.5 text-right text-sm">
                    <span class="text-xs text-secondary">{{ $product->unit->symbol }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border bg-white p-8 text-center text-sm text-secondary">Belum ada produk aktif.</div>
        @endforelse
        {{ $products->links() }}
        <button class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white">Simpan batas di halaman ini</button>
    </form>
    @endif
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('low-stock.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke alert</a>
    <div>
        <h1 class="font-poppins text-2xl font-semibold">Atur batas stok minimum</h1>
        <p class="mt-1 text-sm text-gray-500">Batas, saldo, dan status dihitung per produk pada lokasi yang dipilih. Nilai 0 menonaktifkan alert.</p>
    </div>

    @if(session('status'))<p class="rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>@endif
    @if($errors->any())<p class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</p>@endif

    <form method="GET" action="{{ route('low-stock.thresholds') }}" class="max-w-sm rounded-xl border border-[#EAEAEA] bg-white p-4">
        <label class="text-sm font-semibold">Lokasi
            <select name="location_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border p-2.5 text-sm">
                @foreach($locations as $location)<option value="{{ $location->id }}" @selected($locationId == $location->id)>{{ $location->name }}</option>@endforeach
            </select>
        </label>
    </form>

    @if($locationId)
    <form method="POST" action="{{ route('low-stock.thresholds.save') }}" class="overflow-hidden rounded-xl border border-[#EAEAEA] bg-white">
        @csrf @method('PUT')
        <input type="hidden" name="location_id" value="{{ $locationId }}">
        <input type="hidden" name="page" value="{{ $products->currentPage() }}">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr><th class="p-4">Produk</th><th class="p-4">Kategori</th><th class="p-4">Batas Minimum</th><th class="p-4">Saldo Saat Ini</th><th class="p-4">Status</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($products as $product)
                        @php($alertState = $alertStates[$product->id])
                        <tr>
                            <td class="p-4"><b>{{ $product->name }}</b><small class="block text-gray-500">{{ $product->sku }}</small></td>
                            <td class="p-4">{{ $product->category->name }}</td>
                            <td class="p-4">
                                <div class="flex max-w-xs items-center gap-2">
                                    <input required type="number" name="thresholds[{{ $product->id }}]" min="0" step="0.001" value="{{ \App\Support\QuantityFormatter::formatInput(old('thresholds.'.$product->id, $thresholds[$product->id] ?? '0')) }}" aria-label="Batas minimum {{ $product->name }}" class="w-36 rounded-lg border p-2.5 text-sm">
                                    <span class="text-gray-500">{{ $product->unit->symbol }}</span>
                                </div>
                            </td>
                            <td class="p-4 font-semibold tabular-nums">{{ \App\Support\QuantityFormatter::format($alertState['balance']) }} {{ $product->unit->symbol }}</td>
                            <td class="p-4">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-gray-100 text-gray-600' => $alertState['state'] === 'Nonaktif',
                                    'bg-red-50 text-red-700' => $alertState['state'] === 'Stok rendah',
                                    'bg-green-50 text-green-700' => $alertState['state'] === 'Aman',
                                ])>{{ $alertState['state'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">Belum ada produk aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4">{{ $products->links() }}<button class="rounded-xl bg-[#DC143C] px-5 py-2.5 text-sm font-semibold text-white">Simpan batas di halaman ini</button></div>
    </form>
    @endif
</section>
@endsection
