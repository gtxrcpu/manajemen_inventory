<label class="block text-sm font-semibold">
    Lokasi yang dihitung <span class="text-red-600">*</span>
    @if($locationScoped)
        <input type="hidden" name="location_id" value="{{ $selectedLocation->id }}">
        <span class="mt-1 block rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-medium text-gray-800">{{ $selectedLocation->name }}</span>
        <span class="mt-1 block text-xs font-normal text-secondary">Lokasi mengikuti penugasan akun Anda.</span>
    @else
    <select required name="location_id" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm">
        <option value="">Pilih lokasi</option>
        @foreach($locations as $location)
            <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
        @endforeach
    </select>
    @endif
</label>

<label class="block text-sm font-semibold">
    Periode <span class="text-red-600">*</span>
    <select required name="period_type" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm">
        <option value="weekly" @selected(old('period_type') === 'weekly')>Mingguan</option>
        <option value="month_end" @selected(old('period_type') === 'month_end')>Akhir bulan</option>
    </select>
</label>

<fieldset data-weekly-scope class="space-y-2 rounded-xl border border-outline p-3">
    <legend class="px-1 text-sm font-semibold">Cakupan hitung mingguan</legend>
    <label class="flex items-start gap-2 text-sm">
        <input type="radio" name="count_scope" value="partial" @checked(old('count_scope', 'partial') === 'partial')>
        <span>
            <b>Cycle count bergilir</b>
            <span class="block text-xs text-secondary">Pilih barang berisiko tinggi, bernilai/bergerak tinggi, mendekati kedaluwarsa, atau pernah berselisih.</span>
        </span>
    </label>
    <label class="flex items-start gap-2 text-sm">
        <input type="radio" name="count_scope" value="full" @checked(old('count_scope') === 'full')>
        <span>Hitung semua produk aktif</span>
    </label>
</fieldset>

<div data-weekly-products class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-outline p-3">
    <p class="text-xs text-secondary">Pilih produk yang dihitung. Produk terpilih sementara tidak dapat menerima atau mengeluarkan stok melalui sistem sampai hasil hitung dikirim.</p>
    <input type="search" data-product-search placeholder="Cari nama atau SKU" class="w-full rounded-lg border p-2 text-sm">

    @error('product_ids')
        <p class="text-xs text-red-700">{{ $message }}</p>
    @enderror

    @foreach($products as $product)
        <label data-opname-product data-search="{{ strtolower($product->name.' '.$product->sku) }}" class="flex items-center gap-2 rounded-lg border border-gray-100 p-2 text-sm">
            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, old('product_ids', [])))>
            <span><b>{{ $product->name }}</b><small class="ml-1 text-secondary">{{ $product->sku }}</small></span>
        </label>
    @endforeach
</div>

<p data-month-end-note hidden class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900">Akhir bulan selalu menghitung seluruh produk aktif di lokasi untuk rekonsiliasi Finance.</p>

<label class="block text-sm font-semibold">
    Catatan (opsional)
    <textarea name="notes" maxlength="5000" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm">{{ old('notes') }}</textarea>
</label>

<button type="submit" data-confirm-title="Mulai stock opname?" data-confirm-message="Sistem akan menyimpan snapshot saldo saat ini di lokasi terpilih sebagai dasar penghitungan." data-confirm-button="Ya, mulai hitung" class="rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white">Buat snapshot &amp; mulai hitung</button>
