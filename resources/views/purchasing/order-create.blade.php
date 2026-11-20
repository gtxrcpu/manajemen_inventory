@extends('layouts.dashboard')
@php
    $isEditing = isset($purchaseOrder) && $purchaseOrder;
    $isFromRequest = isset($purchaseRequest) && $purchaseRequest;
    $pageTitle = $isEditing ? 'Ubah Draft Purchase Order' : 'Buat Purchase Order';
    $formAction = $isEditing ? route('purchasing.orders.update', $purchaseOrder) : ($isFromRequest ? route('purchasing.orders.store', $purchaseRequest) : route('purchasing.orders.direct.store'));
    $oldItems = old('items', $items->all());
    $poProducts = $products->map(fn ($product) => [
        'id' => $product->id,
        'sku' => $product->sku,
        'name' => $product->name,
        'unit' => $product->unit->symbol,
    ])->values();
@endphp
@section('mobile-content')
<main class="mx-auto w-full max-w-4xl space-y-4 p-4">
    <a href="{{ $isFromRequest ? route('purchasing.requests.show', $purchaseRequest) : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600"><span class="material-symbols-outlined text-lg">arrow_back</span>Kembali</a>
    @include('purchasing._order-fields', ['mobile' => true])
</main>
@endsection
@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <a href="{{ $isFromRequest ? route('purchasing.requests.show', $purchaseRequest) : route('purchasing.index') }}" class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-600"><span class="material-symbols-outlined text-lg">arrow_back</span>Kembali ke pengadaan</a>
    @include('purchasing._order-fields', ['mobile' => false])
</section>
@endsection
@section('page-scripts')
const poProducts = @json($poProducts);
const poSeedItems = @json($oldItems);
const poCurrency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 });
function recalcPo(form) {
    let subtotal = 0;
    form.querySelectorAll('[data-po-row]').forEach(row => {
        const qty = Number(row.querySelector('[data-po-qty]').value || 0);
        const price = Number(row.querySelector('[data-po-price]').value || 0);
        const discount = Number(row.querySelector('[data-po-discount]').value || 0);
        const line = Math.max(0, Math.round((qty * price - discount) * 100) / 100);
        row.querySelector('[data-po-line-total]').textContent = poCurrency.format(line);
        subtotal += line;
    });
    const tax = subtotal * Number(form.querySelector('[name=tax_rate]').value || 0) / 100;
    const shipping = Number(form.querySelector('[name=shipping_cost]').value || 0);
    const other = Number(form.querySelector('[name=other_cost]').value || 0);
    form.querySelector('[data-po-subtotal]').textContent = poCurrency.format(subtotal);
    form.querySelector('[data-po-tax]').textContent = poCurrency.format(tax);
    form.querySelector('[data-po-shipping]').textContent = poCurrency.format(shipping);
    form.querySelector('[data-po-other]').textContent = poCurrency.format(other);
    form.querySelector('[data-po-grand]').textContent = poCurrency.format(subtotal + tax + shipping + other);
}
function addPoRow(form, seed = {}) {
    const box = form.querySelector('[data-po-items]');
    const idx = `line-${Date.now()}-${Math.floor(Math.random()*9999)}`;
    const row = document.createElement('div'); row.dataset.poRow = ''; row.className = 'grid grid-cols-2 items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-12 md:gap-4';
    const productWrap = document.createElement('label'); productWrap.className = 'col-span-2 min-w-0 text-xs font-semibold text-slate-700 md:col-span-4'; productWrap.append('Barang');
    const select = document.createElement('select'); select.required=true; select.name=`items[${idx}][product_id]`; select.className='mt-1 block min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm';
    select.innerHTML='<option value="">Pilih barang</option>';
    poProducts.forEach(product => { const option=document.createElement('option'); option.value=product.id; option.textContent=`${product.sku} — ${product.name} (${product.unit})`; select.appendChild(option); });
    select.value=seed.product_id ?? ''; productWrap.append(select);
    const field=(label,key,type='number',cls='')=>{ const wrap=document.createElement('label'); wrap.className=`min-w-0 text-xs font-semibold text-slate-700 ${cls}`; wrap.append(label); const input=document.createElement('input'); input.type=type; input.name=`items[${idx}][${key}]`; input.required=key!=='description'&&key!=='discount_amount'; input.className='mt-1 block min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm'; if(type==='number'){input.min='0';input.step=key==='quantity'?'0.001':'0.01';} wrap.append(input); return [wrap,input]; };
    const [qtyWrap,qty]=field('Jumlah dipesan','quantity','number','md:col-span-2'); qty.dataset.poQty=''; qty.value=seed.quantity ?? '';
    const [priceWrap,price]=field('Harga satuan','unit_price','text','md:col-span-2'); price.dataset.poPrice=''; price.inputMode='decimal'; price.placeholder='0.00'; price.pattern='[0-9]+(\\.[0-9]{1,2})?'; price.title='Gunakan titik untuk angka desimal, contoh 5000.00'; price.value=seed.unit_price === undefined || seed.unit_price === null || seed.unit_price === '' ? '' : Number(seed.unit_price).toFixed(2);
    const [discountWrap,discount]=field('Diskon baris','discount_amount','number','md:col-span-2'); discount.dataset.poDiscount=''; discount.value=seed.discount_amount ?? 0;
    const [descriptionWrap,description]=field('Spesifikasi','description','text','col-span-2 md:col-span-8'); description.value=seed.description ?? ''; description.placeholder='Merek, kemasan, dll.';
    const total=document.createElement('div'); total.className='col-span-2 min-w-0 rounded-lg bg-white px-3 py-2 text-sm font-bold text-slate-900 md:col-span-4'; total.innerHTML='<span class="text-[10px] font-medium text-slate-500">Subtotal baris</span><strong data-po-line-total class="mt-0.5 block">Rp0</strong>';
    const remove=document.createElement('button'); remove.type='button'; remove.className='min-h-11 w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 md:col-span-2'; remove.textContent='Hapus barang'; remove.addEventListener('click',()=>{row.remove();recalcPo(form);});
    row.append(productWrap,qtyWrap,priceWrap,discountWrap,remove,descriptionWrap,total); box.append(row);
    row.querySelectorAll('input').forEach(input=>input.addEventListener('input',()=>recalcPo(form))); select.addEventListener('change',()=>recalcPo(form));
}
document.querySelectorAll('[data-po-form]').forEach(form=>{
    poSeedItems.forEach(item=>addPoRow(form,item)); if(!poSeedItems.length)addPoRow(form);
    form.querySelector('[data-po-add]').addEventListener('click',()=>addPoRow(form));
    form.querySelectorAll('[name=tax_rate],[name=shipping_cost],[name=other_cost]').forEach(input=>input.addEventListener('input',()=>recalcPo(form)));
    recalcPo(form);
});
@endsection
