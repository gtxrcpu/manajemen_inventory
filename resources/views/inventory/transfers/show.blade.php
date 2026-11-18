@extends('layouts.dashboard')
@php
    $pageTitle = 'Detail Transfer '.$transfer->reference;
    $statusMap = [
        'pending_approval'            => ['label' => 'Menunggu Persetujuan PIC Gudang', 'style' => 'bg-amber-50 text-amber-900 border border-amber-200'],
        'in_transit'                  => ['label' => 'Dalam Perjalanan',          'style' => 'bg-blue-50 text-blue-700 border border-blue-200'],
        'partially_received'          => ['label' => 'Diterima Sebagian',         'style' => 'bg-amber-50 text-amber-800 border border-amber-200'],
        'discrepancy_open'            => ['label' => 'Discrepancy terbuka',       'style' => 'bg-rose-50 text-rose-800 border border-rose-200'],
        'received'                    => ['label' => 'Diterima',                  'style' => 'bg-emerald-50 text-emerald-800 border border-emerald-200'],
        'received_with_discrepancy'   => ['label' => 'Selisih Sudah Diputuskan', 'style' => 'bg-orange-50 text-orange-800 border border-orange-200'],
    ];
    $currentStatus = $statusMap[$transfer->status] ?? ['label' => $transfer->status, 'style' => 'bg-slate-100 text-slate-700'];
    if ($transfer->status === 'in_transit' && $transfer->expected_arrival_at?->isPast()) {
        $currentStatus = ['label' => 'Terlambat', 'style' => 'bg-rose-50 text-rose-800 border border-rose-200'];
    }
    $completedItemLines = $transfer->items->filter(fn($item) => (float) $item->received_quantity + 0.0005 >= (float) $item->quantity)->count();
@endphp

@section('mobile-content')
<main class="mx-auto w-full space-y-4 p-4">
    @include('inventory.transfers._detail-content')
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-4">
    @include('inventory.transfers._detail-content')
</section>
@endsection
