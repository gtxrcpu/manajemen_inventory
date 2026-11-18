@extends('layouts.dashboard')
@php($pageTitle = 'Audit Aktivitas')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <div><h1 class="font-poppins text-xl font-semibold text-gray-900">Audit Aktivitas</h1><p class="mt-1 text-xs text-gray-500">Catatan perubahan dan transaksi yang tercatat di sistem.</p></div>

    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">Periksa kembali filter tanggal yang dipilih.</div>@endif

    <form method="GET" action="{{ route('admin.audit.index') }}" class="space-y-3 rounded-xl border border-gray-200 bg-white p-4">
        <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Cari aktivitas</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Aksi, pengguna, atau data" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
        <div class="grid grid-cols-2 gap-2">
            <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Aksi</span><select name="action" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-xs"><option value="">Semua aksi</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ str_replace(['.', '_'], ' ', $action) }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Pengguna</span><select name="actor_id" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-xs"><option value="">Semua pengguna</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected(($filters['actor_id'] ?? '') == $actor->id)>{{ $actor->name }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Dari tanggal</span><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></label>
            <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Sampai tanggal</span><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-xs"></label>
        </div>
        <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-700">Jenis data</span><select name="entity" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><option value="">Semua jenis data</option>@foreach($entities as $entity)<option value="{{ $entity }}" @selected(($filters['entity'] ?? '') === $entity)>{{ class_basename($entity) }}</option>@endforeach</select></label>
        <div class="grid grid-cols-2 gap-2"><button class="rounded-lg bg-gray-900 px-3 py-2.5 text-sm font-semibold text-white">Terapkan filter</button><a href="{{ route('admin.audit.index') }}" class="rounded-lg border border-gray-300 px-3 py-2.5 text-center text-sm font-semibold text-gray-700">Reset</a></div>
    </form>

    <div class="flex items-center justify-between"><h2 class="font-semibold text-gray-900">Aktivitas terbaru</h2><span class="text-xs text-gray-500">{{ number_format($logs->total(), 0, ',', '.') }} catatan</span></div>
    <div class="space-y-3">
        @forelse($logs as $log)
        <article class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="break-words text-sm font-semibold text-gray-900">{{ str_replace(['.', '_'], ' ', $log->action) }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->actor?->name ?? 'Pengguna dihapus' }} · {{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</p></div><time class="shrink-0 text-right text-[11px] text-gray-500" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d M Y') }}<br>{{ $log->created_at->format('H:i') }}</time></div>
            @if($log->changes)<details class="mt-3 border-t border-gray-100 pt-2"><summary class="cursor-pointer text-xs font-medium text-gray-600">Lihat detail perubahan</summary><pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 text-[11px] text-gray-700">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif
        </article>
        @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center"><span class="material-symbols-outlined text-2xl text-gray-400">history</span><p class="mt-2 text-sm font-medium text-gray-800">Tidak ada aktivitas ditemukan</p><p class="mt-1 text-xs text-gray-500">Ubah filter atau reset untuk melihat semua catatan.</p></div>
        @endforelse
    </div>
    {{ $logs->links() }}
</main>
@endsection

@section('desktop-content')
<section class="space-y-5">
    <div><h1 class="font-poppins text-2xl font-semibold text-gray-900">Audit Aktivitas</h1><p class="mt-1 text-sm text-gray-500">Catatan perubahan dan transaksi yang tercatat di sistem.</p></div>

    @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Periksa kembali filter tanggal yang dipilih.</div>@endif

    <form method="GET" action="{{ route('admin.audit.index') }}" class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="grid items-end gap-3 md:grid-cols-2 xl:grid-cols-8">
            <label class="block xl:col-span-2"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Cari aktivitas</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Aksi, pengguna, atau data" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Aksi</span><select name="action" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><option value="">Semua aksi</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ str_replace(['.', '_'], ' ', $action) }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Pengguna</span><select name="actor_id" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><option value="">Semua pengguna</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected(($filters['actor_id'] ?? '') == $actor->id)>{{ $actor->name }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Jenis data</span><select name="entity" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm"><option value="">Semua jenis</option>@foreach($entities as $entity)<option value="{{ $entity }}" @selected(($filters['entity'] ?? '') === $entity)>{{ class_basename($entity) }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Dari</span><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold text-gray-700">Sampai</span><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border border-gray-300 p-2.5 text-sm"></label>
            <div class="flex gap-2"><button class="flex-1 rounded-lg bg-gray-900 px-3 py-2.5 text-sm font-semibold text-white">Filter</button><a href="{{ route('admin.audit.index') }}" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-700">Reset</a></div>
        </div>
    </form>

    <div class="flex items-center justify-between"><h2 class="font-semibold text-gray-900">Aktivitas terbaru</h2><span class="text-xs text-gray-500">{{ number_format($logs->total(), 0, ',', '.') }} catatan</span></div>
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
        @forelse($logs as $log)
        <article class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-4"><div class="min-w-0"><p class="break-words font-semibold text-gray-900">{{ str_replace(['.', '_'], ' ', $log->action) }}</p><p class="mt-1 text-xs text-gray-500">{{ $log->actor?->name ?? 'Pengguna dihapus' }} · {{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</p></div><time class="shrink-0 text-right text-xs text-gray-500" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d M Y') }}<br>{{ $log->created_at->format('H:i:s') }}</time></div>
            @if($log->changes)<details class="mt-3 border-t border-gray-100 pt-2"><summary class="cursor-pointer text-xs font-medium text-gray-600">Lihat detail perubahan</summary><pre class="mt-2 max-h-56 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 text-xs text-gray-700">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif
        </article>
        @empty
        <div class="col-span-full rounded-xl border border-gray-200 bg-white p-10 text-center"><span class="material-symbols-outlined text-3xl text-gray-400">history</span><p class="mt-2 font-medium text-gray-800">Tidak ada aktivitas ditemukan</p><p class="mt-1 text-sm text-gray-500">Ubah filter atau reset untuk melihat semua catatan.</p></div>
        @endforelse
    </div>
    {{ $logs->links() }}
</section>
@endsection
