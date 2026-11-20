@php
    $selectedLocationId = old('location_id', $purchaseRequest?->location_id);
    $selectedSupplierId = old('recommended_supplier_id', $purchaseRequest?->recommended_supplier_id);
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <label class="block text-sm font-semibold text-slate-800">Nomor PR <span class="text-rose-600">*</span>
        <input required name="reference" maxlength="100" value="{{ old('reference', $purchaseRequest?->reference) }}" placeholder="PR-2026-001" @if($purchaseRequest) readonly @endif class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-mono outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100 {{ $purchaseRequest ? 'bg-slate-50 text-slate-500' : '' }}">
    </label>
    <label class="block text-sm font-semibold text-slate-800">Lokasi tujuan <span class="text-rose-600">*</span>
        <select required name="location_id" data-pr-location class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">
            <option value="">Pilih gudang atau outlet</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((string)$selectedLocationId === (string)$location->id)>{{ $location->type === 'warehouse' ? 'Gudang' : 'Outlet' }} · {{ $location->name }}</option>
            @endforeach
        </select>
    </label>
    <div class="text-sm font-semibold text-slate-800">
        <label for="pr-priority">Prioritas <span class="text-rose-600">*</span></label>
        <select id="pr-priority" required name="priority" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">
            <option value="normal" @selected(old('priority', $purchaseRequest?->priority ?? 'normal') === 'normal')>Normal</option>
            <option value="emergency" @selected(in_array(old('priority', $purchaseRequest?->priority), ['emergency', 'urgent'], true))>Darurat</option>
        </select>
        <div class="mt-2 space-y-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-normal leading-relaxed text-slate-600">
            <p><strong class="font-semibold text-slate-800">Normal:</strong> masuk antrean sesuai waktu pengajuan.</p>
            <p><strong class="font-semibold text-rose-700">Darurat:</strong> untuk kebutuhan yang berisiko mengganggu operasional; Management meninjau di atas PR Normal.</p>
            <p class="text-slate-500">Darurat tetap perlu persetujuan Management. PO hanya bisa dibuat setelah PR disetujui.</p>
        </div>
    </div>
    <label class="block text-sm font-semibold text-slate-800">Tanggal dibutuhkan
        <input type="date" name="required_date" value="{{ old('required_date', $purchaseRequest?->required_date?->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">
    </label>
    <label class="block text-sm font-semibold text-slate-800 md:col-span-2">Rekomendasi supplier <span class="font-normal text-slate-500">(opsional)</span>
        <select name="recommended_supplier_id" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">
            <option value="">Belum ada rekomendasi</option>
            @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)$selectedSupplierId === (string)$supplier->id)>{{ $supplier->name }}</option>@endforeach
        </select>
    </label>
    <label class="block text-sm font-semibold text-slate-800 md:col-span-2">Alasan pembelian <span class="text-rose-600">*</span>
        <textarea required minlength="5" maxlength="5000" name="justification" rows="3" placeholder="Jelaskan kebutuhan, kondisi stok, atau tujuan pembelian." class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">{{ old('justification', $purchaseRequest?->justification) }}</textarea>
    </label>
</div>

<section class="border-t border-slate-200 pt-5">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">Rincian pengadaan</p><h2 class="mt-1 text-base font-semibold text-slate-900">Barang yang diminta</h2><p class="mt-1 text-xs text-slate-500">Stok dan harga terakhir ditarik otomatis. Harga tetap dapat disesuaikan.</p></div>
        <button type="button" data-pr-add data-target="pr-rows-{{ $suffix }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"><span class="material-symbols-outlined text-[17px]">add</span>Tambah barang</button>
    </div>
    <div id="pr-rows-{{ $suffix }}" data-pr-rows data-suffix="{{ $suffix }}" class="space-y-3"></div>
    <div class="mt-4 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><span class="text-sm font-semibold text-slate-700">Total estimasi anggaran</span><strong data-pr-total class="text-base font-bold tabular-nums text-slate-950">Rp0</strong></div>
</section>

<label class="block text-sm font-semibold text-slate-800">Catatan umum <span class="font-normal text-slate-500">(opsional)</span>
    <textarea name="notes" rows="2" maxlength="5000" placeholder="Catatan tambahan untuk Purchasing atau Management." class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#DC143C] focus:ring-2 focus:ring-rose-100">{{ old('notes', $purchaseRequest?->notes) }}</textarea>
</label>

<div class="flex flex-col-reverse justify-between gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center">
    <a href="{{ route('purchasing.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
    <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b01030]"><span class="material-symbols-outlined text-[18px]">{{ $purchaseRequest ? 'save' : 'send' }}</span>{{ $purchaseRequest ? 'Simpan perubahan PR' : 'Kirim untuk persetujuan' }}</button>
</div>
