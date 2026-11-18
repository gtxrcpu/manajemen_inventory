@extends('layouts.dashboard')
@php($pageTitle = 'Validasi Penerimaan Gudang')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <header>
        <h1 class="text-xl font-semibold">Validasi Penerimaan Gudang</h1>
        <p class="mt-1 text-sm text-secondary">Bandingkan jumlah pada faktur dengan barang yang benar-benar tiba.</p>
    </header>
    <form method="GET" class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Cari PO, supplier, atau faktur" class="min-w-0 flex-1 rounded-xl border border-outline bg-white px-3 py-2.5 text-sm">
        <button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
    </form>
    <section class="space-y-3">
        @forelse($orders as $order)
            @php($invoice = $order->invoices->sortByDesc('created_at')->first())
            <a href="{{ route('purchasing.orders.show', $order) }}" class="block rounded-2xl border border-outline bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="font-semibold">{{ $order->reference }}</p><p class="mt-1 text-xs text-secondary">{{ $order->supplier->name }} · {{ $order->location->name }}</p></div>
                    <span class="rounded-full {{ $invoice ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700' }} px-2.5 py-1 text-[11px] font-semibold">{{ $invoice ? 'Perlu divalidasi' : 'Faktur belum ada' }}</span>
                </div>
                <p class="mt-3 text-xs text-secondary">{{ $order->items->count() }} item · {{ $invoice ? 'Faktur '.$invoice->invoice_number : 'Minta Purchasing menautkan faktur ke PO' }}</p>
                <span class="mt-3 inline-flex text-sm font-semibold text-primary">Buka pemeriksaan &rarr;</span>
            </a>
        @empty
            <div class="rounded-2xl border border-outline bg-white p-6 text-center text-sm text-secondary">Tidak ada PO yang menunggu penerimaan.</div>
        @endforelse
        {{ $orders->links() }}
    </section>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-6xl space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-2xl font-semibold">Validasi Penerimaan Gudang</h1><p class="mt-1 text-sm text-gray-500">Bandingkan jumlah pada faktur dengan barang yang benar-benar tiba.</p></div>
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Cari PO, supplier, atau faktur" class="w-72 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm">
            <button class="rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
        </form>
    </header>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($orders as $order)
            @php($invoice = $order->invoices->sortByDesc('created_at')->first())
            <a href="{{ route('purchasing.orders.show', $order) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-[#DC143C]/40 hover:shadow-md">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs uppercase tracking-wide text-gray-500">Purchase Order</p><h2 class="mt-1 text-lg font-semibold">{{ $order->reference }}</h2></div><span class="rounded-full {{ $invoice ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-700' }} px-3 py-1 text-xs font-semibold">{{ $invoice ? 'Perlu divalidasi' : 'Faktur belum ada' }}</span></div>
                <p class="mt-3 text-sm text-gray-600">{{ $order->supplier->name }} · {{ $order->location->name }}</p>
                <div class="mt-4 rounded-xl bg-gray-50 p-3 text-sm"><p class="text-xs text-gray-500">Faktur terkait</p><p class="mt-1 font-semibold">{{ $invoice?->invoice_number ?? 'Belum ditautkan' }}</p><p class="mt-1 text-xs text-gray-500">{{ $order->items->count() }} item · {{ $order->expected_delivery?->format('d M Y') ?? 'Jadwal belum ditentukan' }}</p></div>
                <span class="mt-4 inline-flex text-sm font-semibold text-[#DC143C]">Buka pemeriksaan &rarr;</span>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">Tidak ada PO yang menunggu penerimaan.</div>
        @endforelse
    </div>
    {{ $orders->links() }}
</section>
@endsection
