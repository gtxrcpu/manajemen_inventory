@extends('layouts.dashboard')
@php($pageTitle = 'Detail '.$disposal->reference.' — Technolife')

@section('mobile-content')
<main class="w-full space-y-4 p-4"><a href="{{ route('inventory.stock-disposals.index') }}" class="text-sm font-semibold text-primary">← Kembali</a>@if(session('status'))<p class="rounded-xl bg-green-50 p-3 text-sm">{{ session('status') }}</p>@endif<section class="rounded-2xl border bg-white p-4"><p class="text-xs text-secondary">{{ str_replace('_',' ',$disposal->type) }} · {{ str_replace('_',' ',ucfirst($disposal->status)) }}</p><h1 class="mt-1 text-xl font-semibold">{{ $disposal->reference }}</h1><p class="mt-1 text-sm">{{ $disposal->location->name }} · {{ $disposal->reason_category }}</p>@foreach($disposal->items as $item)<p class="mt-4 border-t pt-3 text-sm"><b>{{ $item->product->name }}</b> · {{ number_format((float)$item->quantity,3,',','.') }} {{ $item->product->unit->symbol }}<span class="block text-xs text-secondary">Batch: {{ $item->batch?->batch_number ?: 'Tidak ditentukan' }}</span></p>@endforeach<p class="mt-3 text-sm">{{ $disposal->reason }}</p><p class="mt-2 text-xs text-secondary">Diajukan {{ $disposal->creator->name }} · {{ $disposal->created_at->format('d M Y H:i') }}</p>@if($disposal->reviewer)<p class="text-xs text-secondary">Ditinjau {{ $disposal->reviewer->name }} · {{ $disposal->reviewed_at?->format('d M Y H:i') }}@if($disposal->review_note) · {{ $disposal->review_note }}@endif</p>@endif @if($disposal->invoice)<p class="mt-2 text-xs">Invoice asal: {{ $disposal->invoice->invoice_number }}</p>@endif</section>
@if($canApprove && $disposal->status==='pending_approval')<form method="POST" action="{{ route('inventory.stock-disposals.approve',$disposal) }}" class="rounded-xl border bg-white p-4">@csrf<label class="block text-sm">Catatan review (opsional)<textarea name="review_note" class="mt-1 w-full rounded border p-2"></textarea></label><button class="mt-3 w-full rounded bg-green-600 p-2 text-sm font-semibold text-white">Setujui dan kurangi saldo</button></form><form method="POST" action="{{ route('inventory.stock-disposals.reject',$disposal) }}" class="rounded-xl border bg-white p-4">@csrf<label class="block text-sm">Alasan penolakan<textarea required name="review_note" minlength="3" class="mt-1 w-full rounded border p-2"></textarea></label><button class="mt-3 w-full rounded bg-red-600 p-2 text-sm font-semibold text-white">Tolak pengajuan</button></form>@endif@include('inventory.stock-disposals._supplier-resolution') </main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-3xl space-y-4"><a href="{{ route('inventory.stock-disposals.index') }}" class="text-sm font-semibold text-primary">← Kembali</a>@if(session('status'))<p class="rounded-xl bg-green-50 p-3 text-sm">{{ session('status') }}</p>@endif<section class="rounded-2xl border bg-white p-6"><p class="text-xs uppercase text-gray-500">{{ str_replace('_',' ',$disposal->type) }} · {{ str_replace('_',' ',ucfirst($disposal->status)) }}</p><h1 class="mt-1 text-2xl font-semibold">{{ $disposal->reference }}</h1><p class="mt-1">{{ $disposal->location->name }} · {{ $disposal->reason_category }}</p><div class="mt-4 divide-y">@foreach($disposal->items as $item)<p class="py-3 text-sm"><b>{{ $item->product->name }}</b> · {{ number_format((float)$item->quantity,3,',','.') }} {{ $item->product->unit->symbol }} · Batch {{ $item->batch?->batch_number ?: 'Tidak ditentukan' }}</p>@endforeach</div><p class="border-t pt-4 text-sm">{{ $disposal->reason }}</p><p class="mt-2 text-xs text-gray-500">Diajukan {{ $disposal->creator->name }} · {{ $disposal->created_at->format('d M Y H:i') }}</p>@if($disposal->reviewer)<p class="text-xs text-gray-500">Ditinjau {{ $disposal->reviewer->name }} · {{ $disposal->reviewed_at?->format('d M Y H:i') }} · {{ $disposal->review_note }}</p>@endif @if($disposal->invoice)<p class="mt-2 text-sm">Invoice asal: {{ $disposal->invoice->invoice_number }}</p>@endif</section>
@if($canApprove && $disposal->status==='pending_approval')<div class="grid gap-4 md:grid-cols-2"><form method="POST" action="{{ route('inventory.stock-disposals.approve',$disposal) }}" class="rounded-xl border bg-white p-4">@csrf<label class="block text-sm">Catatan review (opsional)<textarea name="review_note" class="mt-1 w-full rounded border p-2"></textarea></label><button class="mt-3 w-full rounded bg-green-600 p-2 text-sm font-semibold text-white">Setujui dan kurangi saldo</button></form><form method="POST" action="{{ route('inventory.stock-disposals.reject',$disposal) }}" class="rounded-xl border bg-white p-4">@csrf<label class="block text-sm">Alasan penolakan<textarea required name="review_note" minlength="3" class="mt-1 w-full rounded border p-2"></textarea></label><button class="mt-3 w-full rounded bg-red-600 p-2 text-sm font-semibold text-white">Tolak pengajuan</button></form></div>@endif@include('inventory.stock-disposals._supplier-resolution') </section>
@endsection

@section('page-scripts')
document.querySelectorAll('[data-supplier-resolution-form]').forEach(form => {
    const type = form.querySelector('[data-resolution-type]');
    const item = form.querySelector('[name="stock_disposal_item_id"]');
    const amountField = form.querySelector('[data-resolution-amount-field]');
    const amount = form.querySelector('[name="amount"]');
    const referenceField = form.querySelector('[data-resolution-reference-field]');
    const reference = form.querySelector('[name="reference"]');
    const receiptField = form.querySelector('[data-replacement-receipt-field]');
    const receipt = form.querySelector('[name="replacement_stock_in_id"]');
    const quantity = form.querySelector('[name="quantity"]');
    const note = form.querySelector('[name="note"]');
    const refresh = () => {
        const replacement = type.value === 'replacement';
        const money = ['credit_note', 'refund'].includes(type.value);
        amountField.classList.toggle('hidden', !money);
        amount.required = money;
        referenceField.classList.toggle('hidden', replacement);
        reference.required = !replacement;
        receiptField.classList.toggle('hidden', !replacement);
        receipt.required = replacement;
        note.required = type.value === 'claim_rejected';
        quantity.max = item.selectedOptions[0]?.dataset.remaining || '';
        const productId = item.selectedOptions[0]?.dataset.productId;
        [...receipt.options].forEach(option => {
            option.hidden = option.value !== '' && option.dataset.productId !== productId;
        });
        if (receipt.selectedOptions[0]?.hidden) receipt.value = '';
    };
    type.addEventListener('change', refresh);
    item.addEventListener('change', refresh);
    refresh();
});
@endsection
