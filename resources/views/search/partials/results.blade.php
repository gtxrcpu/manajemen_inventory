<form method="GET" action="{{ route('search.index') }}" class="flex gap-2 rounded-2xl border border-outline bg-white p-3">
    <input type="search" name="q" value="{{ $term }}" minlength="2" maxlength="100" autofocus placeholder="Nama produk, SKU, batch, supplier..." class="min-w-0 flex-1 rounded-xl border border-outline px-3 py-2.5 text-sm outline-none focus:border-primary">
    <button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
</form>

@if(mb_strlen($term) < 2)
    <div class="rounded-2xl border border-outline bg-white p-6 text-sm text-secondary">Masukkan minimal 2 karakter untuk mulai mencari.</div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-outline bg-white p-4">
            <h2 class="font-semibold">Produk <span class="text-xs font-normal text-secondary">({{ $products->count() }})</span></h2>
            <div class="mt-3 divide-y divide-outline">
                @forelse($products as $product)
                    <a href="{{ route('inventory.stock.show', $product) }}" class="flex items-center justify-between gap-3 py-3 hover:text-primary">
                        <span><b class="block text-sm">{{ $product->name }}</b><small class="text-xs text-secondary">{{ $product->sku }} · {{ $product->category?->name ?? 'Tanpa kategori' }}</small></span>
                        <span class="text-xs text-secondary">Detail <span aria-hidden="true">→</span></span>
                    </a>
                @empty<p class="py-4 text-sm text-secondary">Produk tidak ditemukan.</p>@endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-outline bg-white p-4">
            <h2 class="font-semibold">Batch <span class="text-xs font-normal text-secondary">({{ $batches->count() }})</span></h2>
            <div class="mt-3 divide-y divide-outline">
                @forelse($batches as $batch)
                    <a href="{{ route('inventory.movements.index', ['product_id' => $batch->product_id, 'location_id' => $batch->stockIn->location_id]) }}" class="block py-3 hover:text-primary">
                        <b class="block text-sm">{{ $batch->batch_number ?: 'Batch tanpa nomor' }} · {{ $batch->product->name }}</b>
                        <small class="text-xs text-secondary">{{ $batch->stockIn->location?->name ?? 'Lokasi tidak tersedia' }} · {{ \App\Support\QuantityFormatter::format($batch->quantity) }} {{ $batch->product->unit->symbol }}</small>
                    </a>
                @empty<p class="py-4 text-sm text-secondary">Batch tidak ditemukan.</p>@endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-outline bg-white p-4 lg:col-span-2">
            <h2 class="font-semibold">Supplier <span class="text-xs font-normal text-secondary">({{ $suppliers->count() }})</span></h2>
            <div class="mt-3 grid gap-x-6 divide-y divide-outline sm:grid-cols-2">
                @forelse($suppliers as $supplier)
                    <div class="py-3">
                        @if(auth()->user()->hasPermissionTo('stock.manage'))
                            <a href="{{ route('inventory.stock-in.index', ['supplier_id' => $supplier->id]) }}" class="font-semibold text-sm hover:text-primary">{{ $supplier->name }}</a>
                        @else
                            <b class="text-sm">{{ $supplier->name }}</b>
                        @endif
                        <p class="text-xs text-secondary">{{ $supplier->contact_person ?: $supplier->phone ?: 'Informasi kontak belum tersedia' }}</p>
                    </div>
                @empty<p class="py-4 text-sm text-secondary">Supplier tidak ditemukan.</p>@endforelse
            </div>
        </section>
    </div>
@endif
