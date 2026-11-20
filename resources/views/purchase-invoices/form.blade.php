@extends('layouts.dashboard')
@php($pageTitle = ($invoice ? 'Ubah Draft Faktur' : 'Catat Faktur Pembelian').' — Technolife')
@php($edit = (bool) $invoice)
@php($selectedOrder = $selectedOrder ?? null)
@php($defaultItems = old('items', $invoice?->items?->toArray() ?? (!empty($initialItems) ? $initialItems : [['product_id'=>'','quantity'=>'','unit_price'=>'']])))

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a class="text-sm font-semibold text-[#DC143C]" href="{{ route('purchase-invoices.index') }}">← Faktur Pembelian</a>
    <div>
        <h1 class="font-poppins text-xl font-semibold">{{ $edit ? 'Ubah Draft Faktur' : 'Catat Faktur Pembelian' }}</h1>
        <p class="mt-1 text-sm text-gray-600">Data mengikuti dokumen supplier. Menyimpan draft belum mengubah stok atau pembayaran.</p>
    </div>

    @if($errors->any())
        <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3">
            <span class="material-symbols-outlined text-[16px] text-red-600 shrink-0 mt-0.5">error</span>
            <div>
                <p class="text-sm font-semibold text-red-700">Periksa kembali data faktur dan item.</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-3.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Supplier</p>
                <p class="mt-2 text-sm font-semibold text-gray-800" data-invoice-summary="supplier">{{ $invoice?->supplier?->name ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-3.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nomor Faktur</p>
                <p class="mt-2 text-sm font-semibold text-gray-800" data-invoice-summary="invoice-number">{{ old('invoice_number', $invoice?->invoice_number ?? '—') }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-3.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PO Terkait</p>
                <p class="mt-2 text-sm font-semibold text-gray-800" data-invoice-summary="purchase-order">{{ $invoice?->order?->reference ?? 'Tidak ditautkan' }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-3.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Faktur</p>
                <p class="mt-2 text-sm font-semibold text-gray-800" data-invoice-summary="invoice-date">{{ old('invoice_date', $invoice?->invoice_date?->format('d M Y') ?? '—') }}</p>
            </div>
    </div>

    <form data-invoice-form method="POST" enctype="multipart/form-data" action="{{ $edit ? route('purchase-invoices.update', $invoice) : route('purchase-invoices.store') }}" class="space-y-4 rounded-2xl border border-gray-100 bg-white p-4">
        @csrf @if($edit)@method('PUT')@endif

        <label class="block text-sm font-medium">
            Supplier
            <select name="supplier_id" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $invoice?->supplier_id ?? $selectedSupplierId ?? '')==$supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="block text-sm font-medium">
            PO terkait (opsional)
            <select name="purchase_order_id" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                <option value="">Tidak ditautkan</option>
                @foreach($orders as $order)
                    <option value="{{ $order->id }}" data-supplier-id="{{ $order->supplier_id }}" data-has-confirmed-receipt="{{ $order->has_confirmed_receipt ? '1' : '0' }}" data-items='@json($order->invoice_prefill_items)' data-tax="{{ $order->tax_amount }}" data-shipping="{{ $order->shipping_cost }}" data-other="{{ $order->other_cost }}" data-discount="{{ $order->items->sum('discount_amount') }}" @selected(old('purchase_order_id', $invoice?->purchase_order_id ?? $selectedOrderId ?? '')==$order->id)>{{ $order->reference }} · {{ $order->supplier->name }}{{ $order->has_confirmed_receipt ? ' · Penerimaan dikonfirmasi' : '' }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-gray-500">Pilih PO untuk mengisi barang, jumlah yang sudah diterima dan dikonfirmasi, serta harga PO secara otomatis.</span>
        </label>

        <div class="grid grid-cols-2 gap-3">
            <label class="block text-sm font-medium">
                Nomor faktur
                <input name="invoice_number" required maxlength="120" value="{{ old('invoice_number', $invoice?->invoice_number) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
            </label>
            <label class="block text-sm font-medium">
                Tanggal faktur
                <input type="date" name="invoice_date" required value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
            </label>
        </div>

        <label class="block text-sm font-medium">
            Total sesuai dokumen (Rp)
            <div class="mt-1.5 relative">
                <input type="text" inputmode="decimal" name="document_total" required value="{{ old('document_total', $invoice?->document_total) }}" class="w-full rounded-xl border border-gray-200 p-3" data-money-input data-currency-input="document_total">
                <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="document_total">Rp 0</div>
            </div>
        </label>

        <section class="space-y-3 rounded-xl border bg-slate-50 p-3">
            <h2 class="text-sm font-semibold">Komponen total faktur</h2>
            <p class="text-xs text-gray-600">Isi sesuai dokumen supplier. Pajak dan biaya belum otomatis dimasukkan ke biaya persediaan.</p>
            <div class="grid grid-cols-2 gap-3">
                <label class="text-xs">Diskon (Rp)<input type="text" inputmode="decimal" name="discount_total" value="{{ old('discount_total', $invoice?->discount_total ?? $selectedOrder?->items?->sum('discount_amount') ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2"></label>
                <label class="text-xs">Pajak (Rp)<input type="text" inputmode="decimal" name="tax_total" value="{{ old('tax_total', $invoice?->tax_total ?? $selectedOrder?->tax_amount ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2"></label>
                <label class="text-xs">Ongkir (Rp)<input type="text" inputmode="decimal" name="shipping_total" value="{{ old('shipping_total', $invoice?->shipping_total ?? $selectedOrder?->shipping_cost ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2"></label>
                <label class="text-xs">Biaya lain (Rp)<input type="text" inputmode="decimal" name="other_fee_total" value="{{ old('other_fee_total', $invoice?->other_fee_total ?? $selectedOrder?->other_cost ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2"></label>
            </div>
            <label class="block text-xs">Alasan selisih dengan total dokumen (wajib bila hitungan komponen berbeda lebih dari Rp0,01)
                <textarea name="total_variance_note" rows="2" maxlength="2000" class="mt-1 w-full rounded-lg border p-2">{{ old('total_variance_note', $invoice?->total_variance_note) }}</textarea>
            </label>
        </section>

        <label class="block text-sm font-medium">
            Dokumen faktur (PDF/JPG/PNG/WebP, maks. 10 MB) @if(!$edit)<span class="text-red-600">*</span>@endif
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" @required(!$edit) class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 text-xs">
            @if($invoice)
                <span class="mt-1.5 block text-xs text-gray-500">Dokumen sekarang: {{ $invoice->document_name }}. Kosongkan jika tetap digunakan.</span>
            @endif
        </label>

        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Detail item</h2>
                <button type="button" id="add-item-mobile" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Item
                </button>
            </div>
            <div id="items-mobile" class="space-y-3">
                @foreach($defaultItems as $i => $item)
                    <div class="item-row space-y-3 rounded-xl border border-gray-100 bg-slate-50 p-3">
                        <label class="block text-xs font-medium text-gray-600">
                            Barang
                            <select name="items[{{ $i }}][product_id]" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                                <option value="">Pilih barang</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '')==$product->id)>{{ $product->name }} · {{ $product->sku }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-medium text-gray-600">
                                Kuantitas
                                <input type="number" name="items[{{ $i }}][quantity]" data-field="quantity" data-currency-qty="items[{{ $i }}][quantity]" min="0.001" step="0.001" value="{{ $item['quantity'] ?? '' }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                            </label>
                            <label class="block text-xs font-medium text-gray-600">
                                Harga satuan
                                <input type="text" inputmode="decimal" name="items[{{ $i }}][unit_price]" data-field="unit_price" data-money-input data-currency-price="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                                <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="items[{{ $i }}][unit_price]">Rp 0</div>
                            </label>
                        </div>
                        <div class="text-xs font-semibold text-[#DC143C]" data-subtotal-row="items[{{ $i }}]">— × Rp 0 = Rp 0</div>
                        <button type="button" class="remove-item w-full text-center text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg py-2">Hapus item</button>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-600">Total dokumen harus sesuai subtotal item setelah komponen. Catat alasan selisih di atas bila hasil hitung berbeda lebih dari Rp0,01.</p>
        </section>

        <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-4 py-3 font-semibold text-white hover:bg-[#b01030] transition">
            <span class="material-symbols-outlined text-[18px]">save</span>
            Simpan sebagai draft
        </button>
    </form>

    <template id="item-template-mobile">
        <div class="item-row space-y-3 rounded-xl border border-gray-100 bg-slate-50 p-3">
            <label class="block text-xs font-medium text-gray-600">
                Barang
                <select data-field="product_id" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                    <option value="">Pilih barang</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs font-medium text-gray-600">
                    Kuantitas
                    <input type="number" data-field="quantity" min="0.001" step="0.001" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                </label>
                <label class="block text-xs font-medium text-gray-600">
                    Harga satuan
                    <input type="text" inputmode="decimal" data-field="unit_price" data-money-input required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3">
                    <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="">Rp 0</div>
                </label>
            </div>
            <div class="text-xs font-semibold text-[#DC143C]" data-subtotal-row="">— × Rp 0 = Rp 0</div>
            <button type="button" class="remove-item w-full text-center text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg py-2">Hapus item</button>
        </div>
    </template>

    <script>
    (()=>{
        const parseAmount = value => {
            const amount = Number(String(value ?? '').replace(/\./g, '').replace(',', '.'));
            return Number.isFinite(amount) ? amount : 0;
        };
        const formatRupiah = (num, withDecimals = false) => {
            const parsed = parseAmount(num);
            if (!parsed) return 'Rp 0';
            const n = withDecimals ? parsed : Math.round(parsed);
            const formatted = n.toLocaleString('id-ID');
            return `Rp ${formatted}`;
        };

        const updateCurrencyDisplay = (inputEl) => {
            if (!inputEl) return;
            const outputSelector = inputEl.dataset.currencyOutput || inputEl.dataset.currencyPrice || inputEl.dataset.currencyInput;
            if (!outputSelector) return;
            const output = document.querySelector(`[data-currency-output="${outputSelector}"]`);
            if (output) {
                output.textContent = formatRupiah(inputEl.value);
            }
        };

        const updateSubtotal = (rowEl) => {
            if (!rowEl) return;
            const qtyInput = rowEl.querySelector('[data-field="quantity"]');
            const priceInput = rowEl.querySelector('[data-field="unit_price"]');
            const subtotalDiv = rowEl.querySelector('[data-subtotal-row]');
            if (qtyInput && priceInput && subtotalDiv) {
                const qty = parseFloat(qtyInput.value) || 0;
                const price = parseAmount(priceInput.value);
                const subtotal = Math.round(qty * price * 100) / 100;
                subtotalDiv.textContent = `${qty ? qty.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 3}).replace(/,/g, '.') : '—'} × ${formatRupiah(price)} = Rp ${subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        };

        const list = document.getElementById('items-mobile');
        const template = document.getElementById('item-template-mobile');
        let next = {{ count($defaultItems) }};
        if (list) list.dataset.nextIndex = String(next);

        document.getElementById('add-item-mobile')?.addEventListener('click', () => {
            next = Math.max(next, Number(list.dataset.nextIndex || 0));
            const node = template.content.cloneNode(true);
            node.querySelectorAll('[data-field]').forEach(el => {
                el.name = `items[${next}][${el.dataset.field}]`;
                if (el.dataset.field === 'unit_price') {
                    el.dataset.currencyPrice = `items[${next}][unit_price]`;
                } else if (el.dataset.field === 'quantity') {
                    el.dataset.currencyQty = `items[${next}][quantity]`;
                }
            });
            node.querySelectorAll('[data-currency-output]').forEach(el => {
                el.dataset.currencyOutput = `items[${next}][unit_price]`;
            });
            node.querySelectorAll('[data-subtotal-row]').forEach(el => {
                el.dataset.subtotalRow = `items[${next}]`;
            });
            next++;
            list.dataset.nextIndex = String(next);
            list.append(node);
            initCurrencyFormatters();
        });

        const initCurrencyFormatters = () => {
            document.querySelectorAll('[data-currency-input], [data-currency-price], [data-currency-qty]').forEach(input => {
                updateCurrencyDisplay(input);
                input.removeEventListener('input', handleCurrencyInput);
                input.addEventListener('input', handleCurrencyInput);
            });
        };

        const handleCurrencyInput = (e) => {
            updateCurrencyDisplay(e.target);
            const row = e.target.closest('.item-row');
            if (row) {
                updateSubtotal(row);
            }
        };

        list?.addEventListener('click', e => {
            if (e.target.closest('.remove-item') && list.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
            }
        });

        initCurrencyFormatters();
    })();
    </script>
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-4xl space-y-5">
    <a class="text-sm font-semibold text-[#DC143C]" href="{{ route('purchase-invoices.index') }}">← Faktur Pembelian</a>

    <div>
        <h1 class="font-poppins text-2xl font-semibold">{{ $edit ? 'Ubah Draft Faktur' : 'Catat Faktur Pembelian' }}</h1>
        <p class="mt-1 text-sm text-gray-500">Simpan data sesuai dokumen supplier. Draft belum mengubah stok, valuasi final, atau pembayaran.</p>
    </div>

    @if($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
            <span class="material-symbols-outlined text-[20px] text-red-600 shrink-0">error</span>
            <div>
                <p class="text-sm text-red-700">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    {{-- Info Stat Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {{-- Supplier Card --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Supplier</p>
                    <p class="mt-2 text-lg font-bold text-gray-800" data-invoice-summary="supplier">{{ $invoice?->supplier?->name ?? '—' }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 shrink-0">
                    <span class="material-symbols-outlined text-[22px] text-blue-600">business</span>
                </div>
            </div>
        </div>

        {{-- PO Card --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PO Terkait</p>
                    <p class="mt-2 text-lg font-bold text-gray-800" data-invoice-summary="purchase-order">{{ $invoice?->order?->reference ?? 'Tidak ditautkan' }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 shrink-0">
                    <span class="material-symbols-outlined text-[22px] text-indigo-600">description</span>
                </div>
            </div>
        </div>

        {{-- Nomor Faktur Card --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nomor Faktur</p>
                    <p class="mt-2 text-lg font-bold text-gray-800" data-invoice-summary="invoice-number">{{ old('invoice_number', $invoice?->invoice_number ?? '—') }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 shrink-0">
                    <span class="material-symbols-outlined text-[22px] text-amber-600">receipt_long</span>
                </div>
            </div>
        </div>

        {{-- Tanggal Faktur Card --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Faktur</p>
                    <p class="mt-2 text-lg font-bold text-gray-800" data-invoice-summary="invoice-date">{{ old('invoice_date', $invoice?->invoice_date?->format('d M Y') ?? '—') }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 shrink-0">
                    <span class="material-symbols-outlined text-[22px] text-green-600">calendar_month</span>
                </div>
            </div>
        </div>
    </div>

    <form data-invoice-form method="POST" enctype="multipart/form-data" action="{{ $edit ? route('purchase-invoices.update', $invoice) : route('purchase-invoices.store') }}" class="space-y-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        @csrf @if($edit)@method('PUT')@endif

        <div>
            <h2 class="text-base font-semibold text-gray-800">Informasi Faktur</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-medium text-gray-700">
                    Supplier
                    <select name="supplier_id" required class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition">
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $invoice?->supplier_id ?? $selectedSupplierId ?? '')==$supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700">
                    PO terkait (opsional)
                    <select name="purchase_order_id" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition">
                        <option value="">Tidak ditautkan</option>
                        @foreach($orders as $order)
                        <option value="{{ $order->id }}" data-supplier-id="{{ $order->supplier_id }}" data-has-confirmed-receipt="{{ $order->has_confirmed_receipt ? '1' : '0' }}" data-items='@json($order->invoice_prefill_items)' data-tax="{{ $order->tax_amount }}" data-shipping="{{ $order->shipping_cost }}" data-other="{{ $order->other_cost }}" data-discount="{{ $order->items->sum('discount_amount') }}" @selected(old('purchase_order_id', $invoice?->purchase_order_id ?? $selectedOrderId ?? '')==$order->id)>{{ $order->reference }} · {{ $order->supplier->name }}{{ $order->has_confirmed_receipt ? ' · Penerimaan dikonfirmasi' : '' }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-gray-500">Pilih PO untuk mengisi barang, jumlah yang sudah diterima dan dikonfirmasi, serta harga PO secara otomatis.</span>
                </label>
                <label class="block text-sm font-medium text-gray-700">
                    Nomor faktur
                    <input name="invoice_number" required maxlength="120" value="{{ old('invoice_number', $invoice?->invoice_number) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition">
                </label>
                <label class="block text-sm font-medium text-gray-700">
                    Tanggal faktur
                    <input type="date" name="invoice_date" required value="{{ old('invoice_date', $invoice?->invoice_date?->format('Y-m-d')) }}" class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition">
                </label>
                <label class="block text-sm font-medium text-gray-700">
                    Total sesuai dokumen (Rp)
                    <div class="mt-1.5 relative">
                        <input type="text" inputmode="decimal" name="document_total" required value="{{ old('document_total', $invoice?->document_total) }}" class="w-full rounded-xl border border-gray-200 p-3 focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition" data-money-input data-currency-input="document_total">
                        <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="document_total">Rp 0</div>
                    </div>
                </label>
                <div class="space-y-3 rounded-xl border bg-slate-50 p-4 md:col-span-2">
                    <h3 class="text-sm font-semibold">Komponen total faktur</h3>
                    <p class="text-xs text-gray-600">Isi diskon, pajak, ongkir, dan biaya lain sesuai dokumen. Biaya ini belum otomatis masuk biaya persediaan.</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs">Diskon (Rp)<input type="text" inputmode="decimal" name="discount_total" value="{{ old('discount_total', $invoice?->discount_total ?? $selectedOrder?->items?->sum('discount_amount') ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2.5"></label>
                        <label class="text-xs">Pajak (Rp)<input type="text" inputmode="decimal" name="tax_total" value="{{ old('tax_total', $invoice?->tax_total ?? $selectedOrder?->tax_amount ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2.5"></label>
                        <label class="text-xs">Ongkir (Rp)<input type="text" inputmode="decimal" name="shipping_total" value="{{ old('shipping_total', $invoice?->shipping_total ?? $selectedOrder?->shipping_cost ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2.5"></label>
                        <label class="text-xs">Biaya lain (Rp)<input type="text" inputmode="decimal" name="other_fee_total" value="{{ old('other_fee_total', $invoice?->other_fee_total ?? $selectedOrder?->other_cost ?? 0) }}" data-money-input class="mt-1 w-full rounded-lg border p-2.5"></label>
                    </div>
                    <label class="block text-xs">Alasan selisih dengan total dokumen (wajib bila hitungan komponen berbeda lebih dari Rp0,01)
                        <textarea name="total_variance_note" rows="2" maxlength="2000" class="mt-1 w-full rounded-lg border p-2.5">{{ old('total_variance_note', $invoice?->total_variance_note) }}</textarea>
                    </label>
                </div>
                <label class="block text-sm font-medium text-gray-700">
                    Dokumen faktur (PDF/JPG/PNG/WebP, maks. 10 MB)
                    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" @required(!$edit) class="mt-1.5 w-full rounded-xl border border-gray-200 p-3 text-sm focus:border-[#DC143C] focus:outline-none focus:ring-1 focus:ring-[#DC143C] transition">
                    @if($invoice)
                        <span class="mt-1 block text-xs text-gray-500">Dokumen sekarang: {{ $invoice->document_name }}. Kosongkan untuk tetap menggunakan dokumen saat ini.</span>
                    @endif
                </label>
            </div>
        </div>

        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-800">Detail Item</h2>
            <div class="flex items-center justify-between">
                <button type="button" id="add-item-desktop" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold hover:bg-gray-50 transition">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Tambah item
                </button>
            </div>
            <div id="items-desktop" class="space-y-3">
                @foreach($defaultItems as $i => $item)
                    <div class="item-row rounded-xl border border-gray-100 bg-slate-50 p-4">
                        <div class="grid grid-cols-[1fr_160px_200px_auto] items-start gap-3">
                            <label class="block text-xs font-medium text-gray-600">
                                Barang
                                <select name="items[{{ $i }}][product_id]" required class="mt-1 w-full rounded-xl border border-gray-200 bg-white p-2.5">
                                    <option value="">Pilih barang</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected(($item['product_id'] ?? '')==$product->id)>{{ $product->name }} · {{ $product->sku }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-xs font-medium text-gray-600">
                                Kuantitas
                                <input type="number" name="items[{{ $i }}][quantity]" data-field="quantity" data-currency-qty="items[{{ $i }}][quantity]" min="0.001" step="0.001" value="{{ $item['quantity'] ?? '' }}" required class="mt-1 w-full rounded-xl border border-gray-200 p-2.5">
                            </label>
                            <label class="block text-xs font-medium text-gray-600">
                                Harga satuan
                                <input type="text" inputmode="decimal" name="items[{{ $i }}][unit_price]" data-field="unit_price" data-money-input data-currency-price="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" required class="mt-1 w-full rounded-xl border border-gray-200 p-2.5">
                                <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="items[{{ $i }}][unit_price]">Rp 0</div>
                            </label>
                            <button type="button" class="remove-item rounded-lg p-2 text-red-500 hover:bg-red-50 transition mt-5">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </div>
                        <div class="mt-2 text-right text-sm font-semibold text-[#DC143C]" data-subtotal-row="items[{{ $i }}]">Subtotal: Rp —</div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-600">Total dokumen harus sesuai subtotal item setelah komponen. Jika selisihnya lebih dari Rp0,01, catat alasan untuk ditinjau Finance.</p>
        </section>

        <div class="flex justify-end pt-2">
            <button class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-6 py-3 font-semibold text-white hover:bg-[#b01030] transition shadow-sm">
                <span class="material-symbols-outlined text-[18px]">save</span>
                Simpan sebagai draft
            </button>
        </div>
    </form>

    <template id="item-template-desktop">
        <div class="item-row rounded-xl border border-gray-100 bg-slate-50 p-4">
            <div class="grid grid-cols-[1fr_160px_200px_auto] items-start gap-3">
                <label class="block text-xs font-medium text-gray-600">
                    Barang
                    <select data-field="product_id" required class="mt-1 w-full rounded-xl border border-gray-200 bg-white p-2.5">
                        <option value="">Pilih barang</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-medium text-gray-600">
                    Kuantitas
                    <input type="number" data-field="quantity" min="0.001" step="0.001" required class="mt-1 w-full rounded-xl border border-gray-200 p-2.5">
                </label>
                <label class="block text-xs font-medium text-gray-600">
                    Harga satuan
                    <input type="text" inputmode="decimal" data-field="unit_price" data-money-input required class="mt-1 w-full rounded-xl border border-gray-200 p-2.5">
                    <div class="mt-1 text-sm font-semibold text-[#DC143C]" data-currency-output="">Rp 0</div>
                </label>
                <button type="button" class="remove-item rounded-lg p-2 text-red-500 hover:bg-red-50 transition mt-5">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                </button>
            </div>
            <div class="mt-2 text-right text-sm font-semibold text-[#DC143C]" data-subtotal-row="">Subtotal: Rp —</div>
        </div>
    </template>

    <script>
    (()=>{
        const parseAmount = value => {
            const amount = Number(String(value ?? '').replace(/\./g, '').replace(',', '.'));
            return Number.isFinite(amount) ? amount : 0;
        };
        const formatRupiah = (num, withDecimals = false) => {
            const parsed = parseAmount(num);
            if (!parsed) return '0';
            const n = withDecimals ? parsed : Math.round(parsed);
            return n.toLocaleString('id-ID');
        };

        const updateCurrencyDisplay = (inputEl) => {
            if (!inputEl) return;
            const outputSelector = inputEl.dataset.currencyOutput || inputEl.dataset.currencyPrice || inputEl.dataset.currencyInput;
            if (!outputSelector) return;
            const output = document.querySelector(`[data-currency-output="${outputSelector}"]`);
            if (output) {
                const formatted = formatRupiah(inputEl.value);
                output.textContent = `Rp ${formatted}`;
            }
        };

        const updateSubtotal = (rowEl) => {
            if (!rowEl) return;
            const qtyInput = rowEl.querySelector('[data-field="quantity"]');
            const priceInput = rowEl.querySelector('[data-field="unit_price"]');
            const subtotalDiv = rowEl.querySelector('[data-subtotal-row]');
            if (qtyInput && priceInput && subtotalDiv) {
                const qty = parseFloat(qtyInput.value) || 0;
                const price = parseAmount(priceInput.value);
                const subtotal = Math.round(qty * price * 100) / 100;
                const qtyFormatted = qty ? qty.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 3}).replace(/,/g, '.') : '—';
                subtotalDiv.textContent = `Subtotal: Rp ${subtotal.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        };

        const list = document.getElementById('items-desktop');
        const template = document.getElementById('item-template-desktop');
        const add = document.getElementById('add-item-desktop');
        let next = {{ count($defaultItems) }};
        if (list) list.dataset.nextIndex = String(next);

        add?.addEventListener('click', () => {
            next = Math.max(next, Number(list.dataset.nextIndex || 0));
            const node = template.content.cloneNode(true);
            node.querySelectorAll('[data-field]').forEach(el => {
                el.name = `items[${next}][${el.dataset.field}]`;
                if (el.dataset.field === 'unit_price') {
                    el.dataset.currencyPrice = `items[${next}][unit_price]`;
                } else if (el.dataset.field === 'quantity') {
                    el.dataset.currencyQty = `items[${next}][quantity]`;
                }
            });
            node.querySelectorAll('[data-currency-output]').forEach(el => {
                el.dataset.currencyOutput = `items[${next}][unit_price]`;
            });
            node.querySelectorAll('[data-subtotal-row]').forEach(el => {
                el.dataset.subtotalRow = `items[${next}]`;
            });
            next++;
            list.dataset.nextIndex = String(next);
            list.append(node);
            initCurrencyFormatters();
        });

        const initCurrencyFormatters = () => {
            document.querySelectorAll('[data-currency-input], [data-currency-price], [data-currency-qty]').forEach(input => {
                updateCurrencyDisplay(input);
                input.removeEventListener('input', handleCurrencyInput);
                input.addEventListener('input', handleCurrencyInput);
            });
        };

        const handleCurrencyInput = (e) => {
            updateCurrencyDisplay(e.target);
            const row = e.target.closest('.item-row');
            if (row) {
                updateSubtotal(row);
            }
        };

        list?.addEventListener('click', e => {
            if (e.target.closest('.remove-item') && list.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
            }
        });

        initCurrencyFormatters();
    })();
    </script>
    <script>
    (() => {
        const forms = [...document.querySelectorAll('[data-invoice-form]')];
        const parseMoney = value => {
            const normalized = String(value ?? '').trim().replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
            if (!normalized || normalized === '-') return NaN;
            const number = Number(normalized);
            return Number.isFinite(number) ? number : NaN;
        };
        const formatMoney = value => {
            const raw = String(value ?? '').replace(/[^\d,-]/g, '');
            if (!raw) return '';
            const negative = raw.startsWith('-') ? '-' : '';
            const unsigned = raw.replace(/-/g, '');
            const [integerPart, ...decimalParts] = unsigned.split(',');
            const integer = (integerPart || '0').replace(/^0+(?=\d)/, '');
            const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const decimals = decimalParts.join('').slice(0, 2);
            return `${negative}${grouped}${decimalParts.length ? `,${decimals}` : ''}`;
        };
        const formatInitialMoney = value => {
            const raw = String(value ?? '').trim();
            const canonicalDecimal = raw.match(/^(-?\d+)\.(\d{1,2})$/);
            if (!canonicalDecimal) return formatMoney(raw);
            const decimals = canonicalDecimal[2].replace(/0+$/, '');
            return formatMoney(decimals ? `${canonicalDecimal[1]},${decimals}` : canonicalDecimal[1]);
        };
        const normalizeMoney = value => {
            const number = parseMoney(value);
            return Number.isNaN(number) ? '' : String(number);
        };
        const initMoneyInputs = (root = document) => {
            root.querySelectorAll?.('[data-money-input]').forEach(input => {
                if (input.dataset.moneyReady) return;
                input.dataset.moneyReady = 'true';
                input.value = formatInitialMoney(input.value);
                input.addEventListener('input', () => {
                    const caret = input.selectionStart ?? input.value.length;
                    const digitsBeforeCaret = input.value.slice(0, caret).replace(/\D/g, '').length;
                    const formatted = formatMoney(input.value);
                    input.value = formatted;
                    let nextCaret = 0;
                    let digitCount = 0;
                    while (nextCaret < formatted.length && digitCount < digitsBeforeCaret) {
                        if (/\d/.test(formatted[nextCaret])) digitCount++;
                        nextCaret++;
                    }
                    input.setSelectionRange(nextCaret, nextCaret);
                });
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        };
        const formatDate = value => {
            if (!value) return '—';
            const date = new Date(`${value}T00:00:00Z`);
            return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC'
            }).format(date);
        };
        const setSummary = (key, value) => document.querySelectorAll(`[data-invoice-summary="${key}"]`)
            .forEach(node => { node.textContent = value || '—'; });
        const refreshItemRow = row => {
            const quantity = Number(row.querySelector('[data-field="quantity"]')?.value) || 0;
            const price = parseMoney(row.querySelector('[data-field="unit_price"]')?.value) || 0;
            const output = row.querySelector('[data-currency-output]');
            const subtotal = row.querySelector('[data-subtotal-row]');
            if (output) output.textContent = `Rp ${price.toLocaleString('id-ID', { maximumFractionDigits: 2 })}`;
            if (subtotal) subtotal.textContent = `Subtotal: Rp ${(quantity * price).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };
        const fillItemsFromOrder = (form, option) => {
            let items = [];
            try { items = JSON.parse(option.dataset.items || '[]'); } catch { return; }
            if (!items.length) return;

            const list = form.querySelector('[id^="items-"]');
            if (!list) return;
            const isMobile = list.id === 'items-mobile';
            const template = document.getElementById(isMobile ? 'item-template-mobile' : 'item-template-desktop');
            if (!template) return;

            list.replaceChildren();
            items.forEach((item, index) => {
                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('.item-row');
                row.querySelectorAll('[data-field]').forEach(field => {
                    const fieldName = field.dataset.field;
                    field.name = `items[${index}][${fieldName}]`;
                    field.value = item[fieldName] ?? '';
                    if (fieldName === 'unit_price') field.dataset.currencyPrice = `items[${index}][unit_price]`;
                    if (fieldName === 'quantity') field.dataset.currencyQty = `items[${index}][quantity]`;
                });
                row.querySelectorAll('[data-currency-output]').forEach(output => {
                    output.dataset.currencyOutput = `items[${index}][unit_price]`;
                });
                row.querySelectorAll('[data-subtotal-row]').forEach(subtotal => {
                    subtotal.dataset.subtotalRow = `items[${index}]`;
                });
                list.append(fragment);
                refreshItemRow(list.lastElementChild);
            });
            list.dataset.nextIndex = String(items.length);
            initMoneyInputs(list);
        };
        const syncSummary = form => {
            if (!form) return;
            const supplier = form.querySelector('[name="supplier_id"]');
            const order = form.querySelector('[name="purchase_order_id"]');
            const number = form.querySelector('[name="invoice_number"]');
            const date = form.querySelector('[name="invoice_date"]');
            setSummary('supplier', supplier?.selectedOptions[0]?.textContent.trim() || '—');
            setSummary('purchase-order', order?.selectedOptions[0]?.value ? order.selectedOptions[0].textContent.split('·')[0].trim() : 'Tidak ditautkan');
            setSummary('invoice-number', number?.value.trim() || '—');
            setSummary('invoice-date', formatDate(date?.value));
        };

        initMoneyInputs();
        new MutationObserver(() => initMoneyInputs()).observe(document.body, { childList: true, subtree: true });
        forms.forEach(form => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-money-input]').forEach(input => {
                    input.value = normalizeMoney(input.value);
                });
            });
            form.addEventListener('input', event => {
                if (event.target.matches('[name="invoice_number"], [name="invoice_date"]')) syncSummary(form);
                if (event.target.matches('[data-field="quantity"], [data-field="unit_price"]')) {
                    const row = event.target.closest('.item-row');
                    if (row) refreshItemRow(row);
                }
            });
            form.addEventListener('change', event => {
                if (event.target.matches('[name="supplier_id"]')) {
                    const order = form.querySelector('[name="purchase_order_id"]');
                    if (order?.value && order.selectedOptions[0]?.dataset.supplierId !== event.target.value) {
                        order.value = '';
                    }
                }
                if (event.target.matches('[name="purchase_order_id"]')) {
                    const selectedOption = event.target.selectedOptions[0];
                    const supplierId = selectedOption?.dataset.supplierId;
                    const supplier = form.querySelector('[name="supplier_id"]');
                    if (supplierId && supplier) supplier.value = supplierId;
                    if (event.target.value) {
                        fillItemsFromOrder(form, selectedOption);
                        [['discount_total','discount'],['tax_total','tax'],['shipping_total','shipping'],['other_fee_total','other']].forEach(([name,key]) => {
                            const input = form.querySelector(`[name="${name}"]`);
                            if (input) { input.value = selectedOption.dataset[key] || '0'; input.dispatchEvent(new Event('input', { bubbles: true })); }
                        });
                    }
                }
                if (event.target.matches('[name="supplier_id"], [name="purchase_order_id"], [name="invoice_date"]')) syncSummary(form);
            });
        });
        const visibleForm = forms.find(form => form.getClientRects().length > 0) || forms[0];
        syncSummary(visibleForm);
    })();
    </script>
</section>
@endsection
