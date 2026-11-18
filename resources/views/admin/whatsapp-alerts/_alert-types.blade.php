<section class="grid gap-3 sm:grid-cols-2" aria-label="Jenis alert WhatsApp">
    <label class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 p-4 transition hover:border-rose-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-700">inventory_2</span>
                    <span class="text-sm font-bold text-rose-900">Alert stok minimum</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-rose-900">Dikirim saat stok melewati batas minimum. PIC menerima otomatis untuk outlet; nomor tambahan menerima alert sesuai lokasi.</span>
            </span>
            <input type="checkbox" name="low_stock_enabled" value="1" @checked(old('low_stock_enabled', $settings->low_stock_enabled ?? $settings->enabled)) class="mt-1 h-4 w-4 shrink-0 rounded border-rose-400 text-rose-700 focus:ring-rose-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('low_stock_enabled', $settings->low_stock_enabled ?? $settings->enabled) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('low_stock_enabled', $settings->low_stock_enabled ?? $settings->enabled) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
    <label class="cursor-pointer rounded-xl border border-sky-200 bg-sky-50 p-4 transition hover:border-sky-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sky-700">local_shipping</span>
                    <span class="text-sm font-bold text-sky-900">Kedatangan supplier ke gudang</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-sky-900">Dikirim saat PO diterbitkan. Staf Gudang bernomor aktif menerima otomatis untuk gudangnya.</span>
            </span>
            <input type="checkbox" name="supplier_arrival_enabled" value="1" @checked(old('supplier_arrival_enabled', $settings->supplier_arrival_enabled ?? $settings->enabled)) class="mt-1 h-4 w-4 shrink-0 rounded border-sky-400 text-sky-700 focus:ring-sky-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('supplier_arrival_enabled', $settings->supplier_arrival_enabled ?? $settings->enabled) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('supplier_arrival_enabled', $settings->supplier_arrival_enabled ?? $settings->enabled) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
    <label class="cursor-pointer rounded-xl border border-violet-200 bg-violet-50 p-4 transition hover:border-violet-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-violet-700">fact_check</span>
                    <span class="text-sm font-bold text-violet-900">Persetujuan transfer gudang</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-violet-900">PIC Gudang menerima info transfer dari gudang ke outlet dan menyetujui sebelum stok keluar.</span>
            </span>
            <input type="checkbox" name="transfer_approval_enabled" value="1" @checked(old('transfer_approval_enabled', $settings->transfer_approval_enabled ?? false)) class="mt-1 h-4 w-4 shrink-0 rounded border-violet-400 text-violet-700 focus:ring-violet-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('transfer_approval_enabled', $settings->transfer_approval_enabled ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('transfer_approval_enabled', $settings->transfer_approval_enabled ?? false) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
    <label class="cursor-pointer rounded-xl border border-indigo-200 bg-indigo-50 p-4 transition hover:border-indigo-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-700">departure_board</span>
                    <span class="text-sm font-bold text-indigo-900">Estimasi kedatangan transfer</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-indigo-900">Staff Outlet aktif di lokasi tujuan menerima nomor transfer, daftar barang, dan estimasi tiba setelah PIC Gudang menyetujui pengiriman.</span>
            </span>
            <input type="checkbox" name="transfer_arrival_enabled" value="1" @checked(old('transfer_arrival_enabled', $settings->transfer_arrival_enabled ?? false)) class="mt-1 h-4 w-4 shrink-0 rounded border-indigo-400 text-indigo-700 focus:ring-indigo-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('transfer_arrival_enabled', $settings->transfer_arrival_enabled ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('transfer_arrival_enabled', $settings->transfer_arrival_enabled ?? false) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
    <label class="cursor-pointer rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">inventory</span>
                    <span class="text-sm font-bold text-emerald-900">Konfirmasi penerimaan outlet</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-emerald-900">Staff Gudang di lokasi asal menerima nomor transfer, outlet penerima, waktu konfirmasi, dan jumlah aktual barang yang diterima.</span>
            </span>
            <input type="checkbox" name="transfer_receipt_enabled" value="1" @checked(old('transfer_receipt_enabled', $settings->transfer_receipt_enabled ?? false)) class="mt-1 h-4 w-4 shrink-0 rounded border-emerald-400 text-emerald-700 focus:ring-emerald-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('transfer_receipt_enabled', $settings->transfer_receipt_enabled ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('transfer_receipt_enabled', $settings->transfer_receipt_enabled ?? false) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
    <label class="cursor-pointer rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-300">
        <span class="flex items-start justify-between gap-3">
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">fact_check</span>
                    <span class="text-sm font-bold text-emerald-900">Persetujuan Stock In outlet</span>
                </span>
                <span class="mt-2 block text-xs leading-relaxed text-emerald-900">PIC Outlet menerima alert ketika Staff mengirim penerimaan yang menunggu persetujuan. Stok belum bertambah sampai disetujui.</span>
            </span>
            <input type="checkbox" name="stock_in_approval_enabled" value="1" @checked(old('stock_in_approval_enabled', $settings->stock_in_approval_enabled ?? false)) class="mt-1 h-4 w-4 shrink-0 rounded border-emerald-400 text-emerald-700 focus:ring-emerald-500">
        </span>
        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ old('stock_in_approval_enabled', $settings->stock_in_approval_enabled ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">{{ old('stock_in_approval_enabled', $settings->stock_in_approval_enabled ?? false) ? 'Aktif' : 'Nonaktif' }}</span>
    </label>
</section>
