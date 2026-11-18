@extends('layouts.dashboard')
@php($pageTitle = 'Write-off dan Retur — Technolife')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <a href="{{ route('inventory.stock-out.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary">← Kembali ke Stock Out</a>
    <div class="flex items-start justify-between gap-3"><div><h1 class="font-poppins text-xl font-semibold">Write-off / Retur</h1><p class="text-sm text-secondary">Pengurangan stok dengan review dan jejak audit.</p></div>@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.manage'))<a class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white" href="{{ route('inventory.stock-disposals.create') }}">Buat</a>@endif</div>
    @foreach($disposals as $disposal)<a href="{{ route('inventory.stock-disposals.show',$disposal) }}" class="block rounded-xl border bg-white p-4"><div class="flex justify-between gap-3"><b>{{ $disposal->reference }}</b><span class="text-xs">{{ ($disposal->status === 'pending_approval' ? 'Karantina - menunggu review' : str_replace('_',' ',ucfirst($disposal->status))) }}</span></div><p class="mt-1 text-sm">{{ $disposal->location->name }} · {{ str_replace('_',' ',$disposal->type) }}</p><p class="mt-1 text-xs text-secondary">{{ $disposal->reason_category }} · {{ $disposal->created_at->format('d M Y H:i') }}</p></a>@endforeach
    {{ $disposals->links() }}
</main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('inventory.stock-out.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#DC143C] hover:underline">← Kembali ke Stock Out</a>
    <div class="flex items-center justify-between"><div><h1 class="font-poppins text-2xl font-semibold">Write-off dan retur supplier</h1><p class="mt-1 text-sm text-gray-500">Jumlah yang diajukan dikarantina sementara; saldo fisik berkurang setelah disetujui.</p></div>@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('disposal.manage'))<a class="rounded-lg bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white" href="{{ route('inventory.stock-disposals.create') }}">Buat pengajuan</a>@endif</div><div class="overflow-hidden rounded-2xl border bg-white"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-3">Referensi</th><th class="p-3">Jenis / lokasi</th><th class="p-3">Alasan</th><th class="p-3">Status</th><th class="p-3">Pengaju</th></tr></thead><tbody class="divide-y">@forelse($disposals as $disposal)<tr><td class="p-3"><a class="font-semibold text-primary" href="{{ route('inventory.stock-disposals.show',$disposal) }}">{{ $disposal->reference }}</a></td><td class="p-3">{{ str_replace('_',' ',$disposal->type) }}<span class="block text-xs text-gray-500">{{ $disposal->location->name }}</span></td><td class="p-3">{{ $disposal->reason_category }}</td><td class="p-3">{{ ($disposal->status === 'pending_approval' ? 'Karantina - menunggu review' : str_replace('_',' ',ucfirst($disposal->status))) }}</td><td class="p-3">{{ $disposal->creator->name }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-gray-500">Belum ada pengajuan.</td></tr>@endforelse</tbody></table></div>{{ $disposals->links() }}</section>
@endsection
