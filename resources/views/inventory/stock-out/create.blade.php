@extends('layouts.dashboard')
@php($pageTitle = 'Catat Stock Out — Technolife')
@section('mobile-content')<main class="w-full p-4"><a href="{{ route('inventory.stock-out.index') }}" class="text-sm font-semibold text-primary">← Kembali ke Stock Out</a><h1 class="mt-3 font-poppins text-xl font-semibold">Catat barang keluar</h1><p class="mt-1 text-xs text-secondary">Jumlah tidak bisa melebihi saldo di lokasi asal.</p>@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="stock-out-form-m" data-stock-out-form data-confirm-title="Konfirmasi barang keluar?" data-confirm-message="Stok akan dialokasikan otomatis mengikuti FEFO dan saldo di lokasi asal akan berkurang. Periksa produk dan jumlah sebelum melanjutkan." data-confirm-button="Ya, catat barang keluar" method="POST" action="{{ route('inventory.stock-out.store') }}" class="mt-4 space-y-4 rounded-2xl border border-outline bg-white p-4">@csrf @include('inventory.stock-out._fields',['suffix'=>'m'])</form></main>@endsection
@section('desktop-content')<section class="mx-auto max-w-4xl space-y-5"><a href="{{ route('inventory.stock-out.index') }}" class="text-sm font-semibold text-[#DC143C]">← Kembali ke Stock Out</a><div><h1 class="font-poppins text-2xl font-semibold">Catat barang keluar</h1><p class="mt-1 text-sm text-gray-500">Jumlah tidak bisa melebihi saldo di lokasi asal.</p></div>@if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form id="stock-out-form-d" data-stock-out-form data-confirm-title="Konfirmasi barang keluar?" data-confirm-message="Stok akan dialokasikan otomatis mengikuti FEFO dan saldo di lokasi asal akan berkurang. Periksa produk dan jumlah sebelum melanjutkan." data-confirm-button="Ya, catat barang keluar" method="POST" action="{{ route('inventory.stock-out.store') }}" class="space-y-5 rounded-2xl border border-[#EAEAEA] bg-white p-6">@csrf @include('inventory.stock-out._fields',['suffix'=>'d'])</form></section>@endsection
@section('page-scripts')
    const products = @json($productOptions);
    const batchOptions = @json($batchOptions);

    function getBatchDisplayName(choice) {
        if (choice.id === 'untracked') return 'Stok Reguler (Tanpa Batch)';
        if (!choice.label) return 'Batch';
        const parts = choice.label.split(' · ');
        return parts[0] || 'Batch';
    }

    function getBatchDateBadge(choice) {
        if (choice.expiry_date) {
            return `<span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 border border-amber-200/60"><span class="material-symbols-outlined text-[12px]">event</span>Exp: ${choice.expiry_date}</span>`;
        }
        if (choice.received_at) {
            const dateStr = String(choice.received_at).slice(0, 10);
            return `<span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600"><span class="material-symbols-outlined text-[12px]">calendar_today</span>Masuk: ${dateStr} (${choice.age_days ?? 0} hr)</span>`;
        }
        return '';
    }

    function addStockOutRow(targetId, suffix) {
        const target = document.getElementById(targetId);
        const form = target.closest('form');
        const rowId = 'stock-out-row-' + suffix + '-' + Date.now() + '-' + Math.floor(Math.random()*1000);
        const row = document.createElement('div'); row.id = rowId;
        row.className = 'grid grid-cols-1 gap-2 rounded-xl border border-gray-200 p-3 sm:grid-cols-[1fr_140px_auto] bg-white';
        const select = document.createElement('select'); select.required = true; select.name = `items[${rowId}][product_id]`; select.className = 'rounded-lg border border-gray-300 p-2.5 text-sm';
        select.add(new Option('Pilih produk', ''));
        products.forEach(product => select.add(new Option(`${product.sku} — ${product.name} (${product.unit})`, product.id)));
        const quantity = document.createElement('input'); quantity.required = true; quantity.type = 'number'; quantity.min = '0.001'; quantity.step = '0.001'; quantity.name = `items[${rowId}][quantity]`; quantity.placeholder = 'Jumlah'; quantity.className = 'rounded-lg border border-gray-300 p-2.5 text-sm';
        const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Hapus'; remove.className = 'text-sm font-semibold text-red-600 hover:text-red-800 transition-colors self-center px-2 py-1'; remove.addEventListener('click', () => row.remove());
        const preview = document.createElement('div'); preview.className = 'sm:col-span-3';

        const refreshPreview = () => {
            const locationId = form.querySelector('[name=location_id]').value;
            const issuedAt = form.querySelector('[name=issued_at]').value;
            const selectedProduct = products.find(p => String(p.id) === String(select.value));
            const unit = selectedProduct?.unit || 'unit';
            const reqQty = Number(quantity.value || 0);

            if (!select.value || reqQty <= 0) {
                preview.innerHTML = `
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-2.5 text-xs text-gray-500 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-gray-400">info</span>
                        <span>Pilih produk dan masukkan jumlah untuk melihat alokasi batch otomatis (FEFO).</span>
                    </div>`;
                return;
            }

            const choices = (batchOptions[select.value]?.[locationId] || []).filter(choice => !choice.expiry_date || !issuedAt || choice.expiry_date >= issuedAt);
            if (choices.length === 0) {
                preview.innerHTML = `
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-600">warning</span>
                        <span>Tidak ada stok aktif tersedia untuk produk ini di lokasi yang dipilih.</span>
                    </div>`;
                return;
            }

            let remaining = reqQty;
            const plan = [];
            for (const choice of choices) {
                if (remaining <= 0.0005) break;
                const allocated = Math.min(remaining, Number(choice.quantity));
                if (allocated > 0) {
                    plan.push({
                        choice,
                        allocated,
                        available: Number(choice.quantity),
                        batchName: getBatchDisplayName(choice),
                        dateBadge: getBatchDateBadge(choice),
                    });
                    remaining -= allocated;
                }
            }

            if (remaining > 0.0005) {
                const fulfilled = reqQty - remaining;
                preview.innerHTML = `
                    <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-xs text-red-900 space-y-2">
                        <div class="flex items-center justify-between font-semibold flex-wrap gap-1">
                            <span class="flex items-center gap-1.5 text-red-700">
                                <span class="material-symbols-outlined text-[16px]">error</span>
                                <span>Stok Kurang: Butuh tambahan ${remaining.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit}</span>
                            </span>
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">
                                Hanya ada ${fulfilled.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit}
                            </span>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            ${plan.map((item, idx) => `
                                <div class="flex items-center justify-between rounded-lg border border-red-200/80 bg-white px-2.5 py-1.5 shadow-2xs">
                                    <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[10px] font-bold text-red-700">${idx + 1}</span>
                                        <span class="font-semibold text-slate-800">${item.batchName}</span>
                                        ${item.dateBadge}
                                    </div>
                                    <div class="shrink-0 text-right ml-2">
                                        <span class="font-bold text-red-700">Ambil ${item.allocated.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit}</span>
                                        <span class="text-[10px] text-slate-400 ml-1">(sisa: ${item.available.toLocaleString('id-ID', { maximumFractionDigits: 3 })})</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>`;
                return;
            }

            preview.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 text-xs space-y-2">
                    <div class="flex items-center justify-between text-gray-700 flex-wrap gap-1">
                        <span class="flex items-center gap-1.5 font-semibold text-gray-800">
                            <span class="material-symbols-outlined text-[16px] text-[#DC143C]">auto_schedule</span>
                            <span>Rencana Alokasi FEFO</span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                            <span class="material-symbols-outlined text-[13px]">check_circle</span>
                            Total ${reqQty.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit} terpenuhi
                        </span>
                    </div>
                    <div class="space-y-1.5 pt-0.5">
                        ${plan.map((item, idx) => `
                            <div class="flex items-center justify-between rounded-lg border border-gray-200/80 bg-white px-2.5 py-1.5 shadow-2xs">
                                <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-700">${idx + 1}</span>
                                    <span class="font-semibold text-slate-800">${item.batchName}</span>
                                    ${item.dateBadge}
                                </div>
                                <div class="shrink-0 text-right ml-2">
                                    <span class="font-bold text-[#DC143C]">Ambil ${item.allocated.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${unit}</span>
                                    <span class="text-[10px] text-slate-400 ml-1">(tersedia ${item.available.toLocaleString('id-ID', { maximumFractionDigits: 3 })})</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        };

        select.addEventListener('change', refreshPreview);
        quantity.addEventListener('input', refreshPreview);
        form.querySelector('[name=location_id]').addEventListener('change', refreshPreview);
        form.querySelector('[name=issued_at]').addEventListener('change', refreshPreview);
        row.append(select, quantity, remove, preview); target.appendChild(row);
        refreshPreview();
    }
    document.querySelectorAll('[data-stock-out-add]').forEach(button => button.addEventListener('click', () => addStockOutRow(button.dataset.target, button.dataset.suffix)));
    document.querySelectorAll('[data-stock-out-rows]').forEach(el => addStockOutRow(el.id, el.dataset.suffix));
@endsection
