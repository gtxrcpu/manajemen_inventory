@extends('layouts.dashboard')
@php($pageTitle = 'Laporan')

@extends('layouts.dashboard')
@php($pageTitle = 'Laporan')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <!-- Header -->
    <div>
        <h1 class="font-poppins text-xl font-semibold text-gray-900">Laporan</h1>
        <p class="mt-1 text-xs text-gray-600">Ringkasan stok dan transaksi dari data aktual.</p>
    </div>

    @if($errors->any())
    <div role="alert" class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] text-red-600 shrink-0">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <!-- Filter Form -->
    <form method="GET" action="{{ route('reports.index') }}" class="space-y-3 rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
            <span class="material-symbols-outlined text-gray-600 text-[20px]">filter_alt</span>
            <h2 class="font-semibold text-sm text-gray-900">Filter Laporan</h2>
        </div>

        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">Jenis laporan</span>
            <div class="relative">
                <select name="report" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    @foreach($reportTypes as $key => $label)
                    <option value="{{ $key }}" @selected($reportType === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>

        @if($definition['date'])
        <div class="grid grid-cols-2 gap-2">
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1 block">Dari tanggal</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-xs">
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1 block">Sampai tanggal</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-xs">
            </label>
        </div>
        @endif

        @if($picLocation)
        <input type="hidden" name="location_id" value="{{ $picLocation->id }}">
        <div class="p-2.5 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-xs text-blue-700">Lokasi laporan: <b>{{ $picLocation->name }}</b></p>
        </div>
        @else
        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">Lokasi</span>
            <div class="relative">
                <select name="location_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>
        @endif

        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">Produk</span>
            <div class="relative">
                <select name="product_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    <option value="">Semua produk</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">Kategori</span>
            <div class="relative">
                <select name="category_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>

        @if($transactionTypes)
        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">{{ $reportType === 'purchasing' ? 'Status PO' : 'Jenis transaksi' }}</span>
            <div class="relative">
                <select name="transaction_type" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    <option value="">Semua</option>
                    @foreach($transactionTypes as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['transaction_type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>
        @endif

        @if($definition['date'])
        <label class="block">
            <span class="text-xs font-semibold text-gray-700 mb-1 block">Pengguna</span>
            <div class="relative">
                <select name="user_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                    <option value="">Semua pengguna</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
            </div>
        </label>
        @endif

        <div class="grid grid-cols-2 gap-2 pt-2">
            <button class="rounded-lg bg-gray-900 px-3 py-2.5 text-sm font-semibold text-white flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">search</span>
                <span>Terapkan</span>
            </button>
            <a href="{{ route('reports.export', $filters) }}" class="rounded-lg border border-gray-300 px-3 py-2.5 text-center text-sm font-semibold text-gray-700 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Ekspor CSV</span>
            </a>
        </div>
    </form>

    <!-- Results -->
    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">{{ $definition['label'] }}</h2>
                <p class="text-xs text-gray-500">{{ number_format($rows->total(), 0, ',', '.') }} baris ditemukan</p>
            </div>
        </div>

        @forelse($rows as $row)
        <article class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $row->product ?? $row->reference ?? 'Data laporan' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $row->sku ?? '' }}
                        @if(isset($row->category)) · {{ $row->category }}@endif
                    </p>
                </div>
                @if(isset($row->quantity))
                <div class="text-right shrink-0">
                    <p class="text-base font-semibold text-gray-900 font-poppins">{{ \App\Support\QuantityFormatter::format($row->quantity) }}</p>
                    <p class="text-[10px] text-gray-500 uppercase">{{ $row->unit ?? '' }}</p>
                </div>
                @endif
            </div>

            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-gray-100 pt-3 text-xs">
                @foreach($columns as $key => $label)
                    @if(!in_array($key, ['product','sku','category','quantity','unit'], true) && isset($row->{$key}) && $row->{$key} !== '')
                    <div>
                        <dt class="text-gray-500 mb-0.5">{{ $label }}</dt>
                        <dd class="font-medium text-gray-900">
                            @if($key === 'date')
                                {{ \Illuminate\Support\Carbon::parse($row->{$key})->format('d M Y') }}
                            @elseif($key === 'expiry' && $row->{$key})
                                {{ \Illuminate\Support\Carbon::parse($row->{$key})->format('d M Y') }}
                            @else
                                {{ $row->{$key} }}
                            @endif
                        </dd>
                    </div>
                    @endif
                @endforeach
            </dl>
        </article>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                <span class="material-symbols-outlined text-gray-400 text-[24px]">bar_chart</span>
            </div>
            <p class="text-sm text-gray-500">Belum ada data untuk laporan dan filter ini.</p>
        </div>
        @endforelse

        {{ $rows->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="space-y-5">
    <!-- Header -->
    <div>
        <h1 class="font-poppins text-2xl font-semibold text-gray-900">Laporan</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan stok dan transaksi dari data aktual.</p>
    </div>

    @if($errors->any())
    <div role="alert" class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-2">
        <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">error</span>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    <!-- Filter Form -->
    <form method="GET" action="{{ route('reports.index') }}" class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-200">
            <span class="material-symbols-outlined text-gray-600 text-[22px]">filter_alt</span>
            <h2 class="font-semibold text-gray-900">Filter Laporan</h2>
        </div>

        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-4">
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Jenis laporan</span>
                <div class="relative">
                    <select name="report" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        @foreach($reportTypes as $key => $label)
                        <option value="{{ $key }}" @selected($reportType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>

            @if($definition['date'])
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Dari tanggal</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm">
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Sampai tanggal</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm">
            </label>
            @endif

            @if($picLocation)
            <input type="hidden" name="location_id" value="{{ $picLocation->id }}">
            <div class="self-end p-2.5 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-700">Lokasi: <b>{{ $picLocation->name }}</b></p>
            </div>
            @else
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Lokasi</span>
                <div class="relative">
                    <select name="location_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        <option value="">Semua lokasi</option>
                        @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>
            @endif

            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Produk</span>
                <div class="relative">
                    <select name="product_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        <option value="">Semua produk</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Kategori</span>
                <div class="relative">
                    <select name="category_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        <option value="">Semua kategori</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>

            @if($transactionTypes)
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">{{ $reportType === 'purchasing' ? 'Status PO' : 'Jenis transaksi' }}</span>
                <div class="relative">
                    <select name="transaction_type" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        <option value="">Semua</option>
                        @foreach($transactionTypes as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['transaction_type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>
            @endif

            @if($definition['date'])
            <label class="block">
                <span class="text-xs font-semibold text-gray-700 mb-1.5 block">Pengguna</span>
                <div class="relative">
                    <select name="user_id" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm appearance-none pr-8 bg-white">
                        <option value="">Semua pengguna</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-[20px]">expand_more</span>
                </div>
            </label>
            @endif

            <div class="flex items-end gap-2">
                <button class="flex-1 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 transition-colors flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                    <span>Terapkan</span>
                </button>
                <a href="{{ route('reports.export', $filters) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    <span>CSV</span>
                </a>
            </div>
        </div>
    </form>

    <!-- Results -->
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-5 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">{{ $definition['label'] }}</h2>
                <p class="mt-1 text-xs text-gray-500 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px]">bar_chart</span>
                    <span>{{ number_format($rows->total(), 0, ',', '.') }} baris ditemukan</span>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                    <tr>
                        @foreach($columns as $label)
                        <th class="whitespace-nowrap p-3 font-semibold">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50 transition-colors">
                        @foreach($columns as $key => $label)
                        <td class="whitespace-nowrap p-3">
                            @if($key === 'date' && !empty($row->{$key}))
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-gray-400 text-[14px]">calendar_today</span>
                                    <span>{{ \Illuminate\Support\Carbon::parse($row->{$key})->format('d M Y') }}</span>
                                </div>
                            @elseif($key === 'expiry' && !empty($row->{$key}))
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-orange-500 text-[14px]">event_busy</span>
                                    <span>{{ \Illuminate\Support\Carbon::parse($row->{$key})->format('d M Y') }}</span>
                                </div>
                            @elseif($key === 'quantity' || $key === 'system_quantity' || $key === 'physical_quantity' || $key === 'variance' || $key === 'old_quantity' || $key === 'new_quantity')
                                <span class="font-semibold text-gray-900">{{ $row->{$key} === null ? '—' : \App\Support\QuantityFormatter::format($row->{$key}) }}</span>
                            @elseif($key === 'unit_price' || $key === 'line_total')
                                <span class="font-medium text-gray-900">{{ $row->{$key} === null ? '—' : 'Rp '.number_format((float)$row->{$key}, 0, ',', '.') }}</span>
                            @else
                                {{ $row->{$key} ?? '—' }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="p-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <span class="material-symbols-outlined text-gray-400 text-[32px]">bar_chart</span>
                            </div>
                            <p class="text-gray-500">Belum ada data untuk laporan dan filter ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $rows->links() }}
        </div>
    </div>
</section>
@endsection
