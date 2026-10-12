@extends('layouts.dashboard')
@php $pageTitle = 'Stock Opname — Technolife'; @endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-poppins text-xl font-semibold">Stock Opname</h1>
            <p class="mt-0.5 text-xs text-secondary">Hitung fisik stok dan pantau persetujuan.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('opname.manage'))
            <a href="{{ route('inventory.stock-opname.create') }}" class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white">+ Buat</a>
        @endif
    </div>

    @if(session('status'))
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white border border-outline p-4">
            <p class="text-xs text-gray-500">Total Sesi</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl bg-blue-50 border border-blue-200 p-4">
            <p class="text-xs text-blue-600">Sedang Dihitung</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $stats['counting'] }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-xs text-amber-600">Menunggu Persetujuan</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['pending_approval'] }}</p>
        </div>
        <div class="rounded-2xl bg-green-50 border border-green-200 p-4">
            <p class="text-xs text-green-600">Disetujui</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ $stats['approved'] }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($opnames as $opname)
        @php
            $statusMap = ['counting' => ['label' => 'Sedang dihitung', 'class' => 'bg-blue-100 text-blue-800'],
                'pending_approval'   => ['label' => 'Menunggu Persetujuan', 'class' => 'bg-amber-100 text-amber-800'],
                'approved'           => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-800'],
                'rejected'           => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-800']];
            $st = $statusMap[$opname->status] ?? ['label' => $opname->status, 'class' => 'bg-gray-100 text-gray-700'];
        @endphp
        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="block rounded-2xl border border-outline bg-white p-4 hover:border-primary/40 transition-colors">
            <div class="flex items-start justify-between gap-2">
                <p class="font-semibold text-sm text-gray-800">{{ $opname->reference }}</p>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $st['class'] }}">{{ $st['label'] }}</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">
                <span class="material-symbols-outlined text-[13px] align-middle">location_on</span> {{ $opname->location->name }}
                · {{ $opname->period_type === 'weekly' ? 'Mingguan' : 'Akhir Bulan' }}
            </p>
            <p class="mt-0.5 text-xs text-gray-400">{{ $opname->items->count() }} produk · oleh {{ $opname->counter->name }}</p>
        </a>
        @empty
        <div class="rounded-2xl border border-outline bg-white p-8 text-center text-sm text-secondary">Belum ada sesi Stock Opname.</div>
        @endforelse
    </div>
    {{ $opnames->links() }}
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-poppins text-2xl font-semibold">Stock Opname</h1>
            <p class="mt-1 text-sm text-gray-500">Hitung stok fisik, tinjau selisih, dan proses persetujuan.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('opname.manage'))
            <a href="{{ route('inventory.stock-opname.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#DC143C] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[18px]">add</span> Buat Stock Opname
            </a>
        @endif
    </div>

    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            <span class="material-symbols-outlined text-[20px] text-green-600">check_circle</span>
            {{ session('status') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Sesi</p>
                    <p class="mt-2 text-3xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100">
                    <span class="material-symbols-outlined text-[22px] text-gray-600">inventory_2</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Sedang Dihitung</p>
                    <p class="mt-2 text-3xl font-bold text-blue-700">{{ $stats['counting'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-200">
                    <span class="material-symbols-outlined text-[22px] text-blue-700">calculate</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-amber-600 uppercase tracking-wide">Menunggu Persetujuan</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['pending_approval'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-200">
                    <span class="material-symbols-outlined text-[22px] text-amber-700">pending_actions</span>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-green-100 bg-green-50 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-green-600 uppercase tracking-wide">Disetujui</p>
                    <p class="mt-2 text-3xl font-bold text-green-700">{{ $stats['approved'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-200">
                    <span class="material-symbols-outlined text-[22px] text-green-700">task_alt</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Cari referensi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-gray-400">search</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor referensi…" class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status</label>
            <select name="status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua status</option>
                <option value="counting" @selected(request('status') === 'counting')>Sedang dihitung</option>
                <option value="pending_approval" @selected(request('status') === 'pending_approval')>Menunggu persetujuan</option>
                <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Lokasi</label>
            <select name="location_id" class="rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Semua lokasi</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#DC143C] px-4 py-2 text-sm font-semibold text-white hover:bg-[#b01030] transition-colors">
                <span class="material-symbols-outlined text-[16px]">filter_list</span> Filter
            </button>
            <a href="{{ route('inventory.stock-opname.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </a>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-gray-500">inventory_2</span>
                <h2 class="font-semibold text-gray-800">Daftar Stock Opname</h2>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">{{ $opnames->total() }} sesi</span>
            </div>
        </div>
        @php
            $statusMap = [
                'counting'         => ['label' => 'Sedang dihitung',     'class' => 'bg-blue-100 text-blue-800'],
                'pending_approval' => ['label' => 'Menunggu Persetujuan','class' => 'bg-amber-100 text-amber-800'],
                'approved'         => ['label' => 'Disetujui',            'class' => 'bg-green-100 text-green-800'],
                'rejected'         => ['label' => 'Ditolak',              'class' => 'bg-red-100 text-red-800'],
            ];
        @endphp
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Lokasi</th>
                    <th class="px-6 py-3">Jenis</th>
                    <th class="px-6 py-3">Petugas</th>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($opnames as $opname)
                @php $st = $statusMap[$opname->status] ?? ['label' => $opname->status, 'class' => 'bg-gray-100 text-gray-700']; @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="font-semibold text-[#DC143C] hover:underline">{{ $opname->reference }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 text-gray-700">
                            <span class="material-symbols-outlined text-[15px] text-gray-400">location_on</span>
                            {{ $opname->location->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                            {{ $opname->period_type === 'weekly' ? 'Mingguan' : 'Akhir Bulan' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#DC143C]/10 text-xs font-bold text-[#DC143C]">
                                {{ strtoupper(substr($opname->counter->name, 0, 1)) }}
                            </div>
                            <span class="text-gray-700">{{ $opname->counter->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $opname->created_at->format('d M Y') }}
                        <p class="text-xs text-gray-400">{{ $opname->created_at->format('H:i') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $opname->items->count() }} produk</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $st['class'] }}">{{ $st['label'] }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2">inventory_2</span>
                        Belum ada sesi Stock Opname.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-100 px-6 py-3">{{ $opnames->links() }}</div>
    </div>

</section>
@endsection
