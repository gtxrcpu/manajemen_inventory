<div class="space-y-6 p-4 md:space-y-7 md:p-7">
    @if($transferRequest)<input type="hidden" name="request_id" value="{{ $transferRequest->id }}">@endif

    <section aria-labelledby="transfer-meta-title">
        <div class="mb-4 flex items-center gap-3 border-b border-slate-100 pb-3"><span class="font-poppins text-xs font-semibold text-[#DC143C]">01</span><span class="h-px w-6 bg-slate-200"></span><div><h2 id="transfer-meta-title" class="text-sm font-semibold text-slate-900">Identitas dokumen</h2><p class="mt-0.5 text-[11px] text-slate-500">Nomor dan lokasi tujuan pengiriman</p></div></div>
        <div class="grid gap-4 md:grid-cols-2 md:gap-5">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3">
                <p class="text-xs font-semibold text-slate-600">Nomor transfer</p>
                <p class="mt-1.5 font-mono text-sm font-semibold tracking-wide text-slate-800">Dibuat otomatis saat disimpan</p>
                <p class="mt-1 text-[10px] text-slate-500">Nomor unik diterbitkan sistem setelah transfer berhasil dibuat.</p>
            </div>

            @if($isWarehouseStaff)
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3" data-fixed-source-wrapper>
                    <input type="hidden" name="source_location_id" value="{{ $assignedWarehouse->id }}" data-fixed-source>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">Gudang asal · tetap</p>
                    <p class="mt-2 flex items-center gap-2 text-sm font-semibold text-slate-900"><span class="material-symbols-outlined text-[18px] text-[#DC143C]">warehouse</span>{{ $assignedWarehouse->name }}</p>
                </div>
            @else
                <label class="block text-xs font-semibold text-slate-600">Gudang asal <span class="text-[#DC143C]">*</span>
                    <select required name="source_location_id" data-source-location class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-[#DC143C] focus:ring-2 focus:ring-[#DC143C]/10">
                        <option value="">Pilih gudang</option>
                        @foreach($warehouses as $location)<option value="{{ $location->id }}" @selected(old('source_location_id') == $location->id)>{{ $location->name }}</option>@endforeach
                    </select>
                </label>
            @endif

            <label class="block text-xs font-semibold text-slate-600 md:col-span-2">Outlet tujuan <span class="text-[#DC143C]">*</span>
                <select required name="destination_location_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-[#DC143C] focus:ring-2 focus:ring-[#DC143C]/10">
                    <option value="">Pilih outlet penerima</option>
                    @foreach($outlets as $location)<option value="{{ $location->id }}" @selected(old('destination_location_id', $transferRequest?->location_id) == $location->id)>{{ $location->name }}</option>@endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="border-y border-slate-200 py-5 md:py-6" aria-labelledby="transfer-items-title">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-start gap-3"><span class="font-poppins text-xs font-semibold text-[#DC143C]">02</span><span class="h-px w-6 mt-2 bg-slate-200"></span><div><h2 id="transfer-items-title" class="text-sm font-semibold text-slate-900">Rincian barang</h2><p class="mt-0.5 text-[11px] text-slate-500">Stok siap dikirim tampil setelah barang dipilih; alokasi mengikuti FEFO.</p></div></div>
            <button type="button" data-transfer-add class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"><span class="material-symbols-outlined text-[16px]">add</span>Tambah baris</button>
        </div>
        <div class="hidden grid-cols-[minmax(0,1fr)_150px_auto] gap-3 border-y border-slate-100 bg-slate-50 px-3 py-2 text-[9px] font-bold uppercase tracking-[0.12em] text-slate-400 sm:grid"><span>Barang dan saldo tersedia</span><span>Jumlah kirim</span><span class="w-12"></span></div>
        <div data-transfer-items class="space-y-2.5 py-2"></div>
        <p class="mt-2 flex items-start gap-1.5 text-[10px] text-slate-400"><span class="material-symbols-outlined text-[14px]">info</span>Saldo yang terlihat dapat berubah saat ada transaksi lain. Sistem akan memeriksa ulang sebelum pengajuan disimpan.</p>
    </section>

    <section aria-labelledby="transfer-notes-title">
        <div class="mb-3 flex items-center gap-3"><span class="font-poppins text-xs font-semibold text-[#DC143C]">03</span><span class="h-px w-6 bg-slate-200"></span><div><h2 id="transfer-notes-title" class="text-sm font-semibold text-slate-900">Catatan pengiriman</h2><p class="mt-0.5 text-[11px] text-slate-500">Opsional · informasi untuk pemeriksa dan penerima</p></div></div>
        <textarea name="notes" rows="3" maxlength="5000" placeholder="Contoh: prioritas pengiriman, kondisi khusus, atau instruksi penerimaan" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#DC143C] focus:ring-2 focus:ring-[#DC143C]/10">{{ old('notes') }}</textarea>
    </section>

    <footer class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-md text-[11px] leading-relaxed text-slate-500">Pengajuan ini masuk ke antrean PIC Gudang. Stok baru berkurang setelah transfer disetujui.</p>
        <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <a href="{{ route('inventory.transfers.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Batal</a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#DC143C] px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#B51232] focus:outline-none focus:ring-2 focus:ring-[#DC143C]/30"><span class="material-symbols-outlined text-[17px]">send</span>Ajukan ke PIC Gudang</button>
        </div>
    </footer>
</div>
