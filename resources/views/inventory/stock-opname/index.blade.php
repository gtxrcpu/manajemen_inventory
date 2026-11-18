@extends('layouts.dashboard')
@php
    $pageTitle = 'Stock Opname — Technolife';
    $statusMap = [
        'counting' => ['label' => 'Sedang dihitung', 'class' => 'bg-blue-100 text-blue-800', 'icon' => 'calculate', 'border' => 'border-l-blue-500'],
        'pending_approval' => ['label' => 'Menunggu persetujuan', 'class' => 'bg-amber-100 text-amber-800', 'icon' => 'pending_actions', 'border' => 'border-l-amber-500'],
        'approved' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-800', 'icon' => 'task_alt', 'border' => 'border-l-emerald-500'],
        'rejected' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-800', 'icon' => 'cancel', 'border' => 'border-l-rose-500'],
    ];
    $statusSummary = [
        ['status' => 'counting', 'label' => 'Sedang dihitung', 'value' => $stats['counting'], 'icon' => 'calculate', 'surface' => 'border-blue-100 bg-blue-50/80', 'icon_class' => 'bg-blue-100 text-blue-700', 'text_class' => 'text-blue-800', 'bar_class' => 'bg-blue-500'],
        ['status' => 'pending_approval', 'label' => 'Menunggu review', 'value' => $stats['pending_approval'], 'icon' => 'pending_actions', 'surface' => 'border-amber-100 bg-amber-50/80', 'icon_class' => 'bg-amber-100 text-amber-700', 'text_class' => 'text-amber-800', 'bar_class' => 'bg-amber-500'],
        ['status' => 'approved', 'label' => 'Disetujui', 'value' => $stats['approved'], 'icon' => 'task_alt', 'surface' => 'border-emerald-100 bg-emerald-50/80', 'icon_class' => 'bg-emerald-100 text-emerald-700', 'text_class' => 'text-emerald-800', 'bar_class' => 'bg-emerald-500'],
        ['status' => 'rejected', 'label' => 'Ditolak', 'value' => $stats['rejected'], 'icon' => 'cancel', 'surface' => 'border-rose-100 bg-rose-50/80', 'icon_class' => 'bg-rose-100 text-rose-700', 'text_class' => 'text-rose-800', 'bar_class' => 'bg-rose-500'],
    ];
    $activeSessionCount = $stats['counting'] + $stats['pending_approval'];
    $progressDenominator = max(1, (int) $stats['total']);
@endphp

@section('mobile-content')
<main class="w-full space-y-4 p-4">

    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 border-l-4 border-l-[#DC143C] bg-white p-4 shadow-2xs">
        <div>
            <h1 class="font-poppins text-xl font-semibold">Stock Opname</h1>
            <p class="mt-0.5 text-xs text-secondary">Catat jumlah fisik dan tindak lanjuti selisih stok.</p>
        </div>
        @if(auth()->user()->hasPermissionTo('opname.manage'))
            <a href="{{ route('inventory.stock-opname.create') }}" class="rounded-xl bg-primary px-3.5 py-2.5 text-xs font-semibold text-white">Mulai sesi</a>
        @endif
    </div>

    @if(session('status'))
        <p class="rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</p>
    @endif

    <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-3 text-xs leading-relaxed text-blue-900">
        <span class="material-symbols-outlined mt-0.5 text-[17px] text-blue-700">info</span>
        <p>Mulai sesi untuk membuat snapshot saldo. Selesaikan hitung sebelum aktivitas stok dilanjutkan di lokasi tersebut.</p>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Ringkasan siklus Stock Opname">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500">Ringkasan siklus</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $stats['total'] }} sesi terpantau</p></div>
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full {{ $activeSessionCount > 0 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }} px-2.5 py-1 text-[10px] font-semibold"><span class="h-1.5 w-1.5 rounded-full {{ $activeSessionCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>{{ $activeSessionCount }} perlu tindakan</span>
        </div>
        <div class="px-4 pt-3"><div class="flex h-1.5 overflow-hidden rounded-full bg-slate-100" aria-label="Komposisi status sesi">
            @foreach($statusSummary as $item)
                @if($item['value'] > 0)<span class="{{ $item['bar_class'] }} transition-all duration-300" style="width: {{ ($item['value'] / $progressDenominator) * 100 }}%" title="{{ $item['label'] }}: {{ $item['value'] }}"></span>@endif
            @endforeach
        </div></div>
        <div class="grid grid-cols-2 gap-2 p-3.5">
            @foreach($statusSummary as $item)
                <a href="{{ route('inventory.stock-opname.index', ['status' => $item['status']]) }}" class="flex min-h-[60px] items-center justify-between gap-2 rounded-xl border {{ $item['surface'] }} px-2.5 py-2 transition hover:shadow-sm">
                    <span class="flex min-w-0 items-center gap-2"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $item['icon_class'] }}"><span class="material-symbols-outlined text-[17px]">{{ $item['icon'] }}</span></span><span class="truncate text-[10px] font-semibold {{ $item['text_class'] }}">{{ $item['label'] }}</span></span>
                    <span class="font-poppins text-lg font-semibold {{ $item['text_class'] }}">{{ $item['value'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <details class="group overflow-hidden rounded-2xl border border-outline bg-white shadow-2xs">
        <summary class="flex cursor-pointer list-none items-center justify-between p-3.5 text-xs font-semibold text-on-surface">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px] text-primary">tune</span>Cari &amp; filter sesi</span>
            <span class="material-symbols-outlined text-[18px] text-secondary transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <form method="GET" action="{{ route('inventory.stock-opname.index') }}" data-auto-filter class="space-y-2.5 border-t border-outline/50 p-3.5">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nomor sesi opname" class="h-10 w-full rounded-xl border border-outline bg-surface-container-lowest px-3 text-xs text-on-surface outline-none focus:border-primary">
            <div class="grid grid-cols-2 gap-2">
                <select name="status" class="h-10 min-w-0 rounded-xl border border-outline bg-surface-container-lowest px-2 text-xs text-on-surface">
                    <option value="">Semua status</option>
                    @foreach($statusMap as $value => $statusOption)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $statusOption['label'] }}</option>@endforeach
                </select>
                @if(!auth()->user()->isLocationScopedUser())
                    <select name="location_id" class="h-10 min-w-0 rounded-xl border border-outline bg-surface-container-lowest px-2 text-xs text-on-surface">
                        <option value="">Semua lokasi</option>
                        @foreach($locations as $loc)<option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>@endforeach
                    </select>
                @endif
            </div>
            @if(request()->filled('q') || request()->filled('status') || request()->filled('location_id'))
                <div class="text-right"><a href="{{ route('inventory.stock-opname.index') }}" class="text-xs font-medium text-secondary underline underline-offset-2">Reset filter</a></div>
            @endif
        </form>
    </details>

    <div class="space-y-3">
        @forelse($opnames as $opname)
        @php
            $st = $statusMap[$opname->status] ?? ['label' => $opname->status, 'class' => 'bg-gray-100 text-gray-700', 'icon' => 'help'];
        @endphp
        <a href="{{ route('inventory.stock-opname.show', $opname) }}" class="block rounded-2xl border border-slate-200 border-l-4 {{ $st['border'] ?? 'border-l-slate-400' }} bg-white p-4 shadow-2xs transition hover:-translate-y-0.5 hover:shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div class="flex min-w-0 flex-wrap items-center gap-2"><p class="font-semibold text-sm text-gray-800">{{ $opname->reference }}</p>@if(str_starts_with($opname->reference, 'DEMO-SO-'))<span class="rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-violet-700">Data demo</span>@endif</div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $st['class'] }}">{{ $st['label'] }}</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">
                <span class="material-symbols-outlined text-[13px] align-middle">location_on</span> {{ $opname->location->name }}
                · {{ $opname->period_type === 'weekly' ? 'Mingguan' : 'Akhir Bulan' }}
            </p>
            <p class="mt-0.5 text-[11px] text-gray-400">Snapshot {{ $opname->snapshot_at->format('d M Y, H:i') }} · {{ $opname->items->count() }} produk · {{ $opname->counter->name }}</p>
            <div class="mt-2 flex items-center justify-end gap-2 text-[11px]"><span class="font-semibold text-primary">{{ $opname->status === 'counting' ? 'Lanjut hitung' : ($opname->status === 'pending_approval' && auth()->user()->hasPermissionTo('opname.approve') ? 'Tinjau hasil' : 'Buka sesi') }} <span aria-hidden="true">→</span></span></div>
        </a>
        @empty
        <div class="rounded-2xl border border-outline bg-white p-8 text-center text-sm text-secondary">
            @if(request()->filled('q') || request()->filled('status') || request()->filled('location_id'))
                Tidak ada sesi yang cocok. <a href="{{ route('inventory.stock-opname.index') }}" class="font-semibold text-primary underline">Reset filter</a>
            @else
                Belum ada sesi Stock Opname.
            @endif
        </div>
        @endforelse
    </div>
    {{ $opnames->links() }}
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4 rounded-3xl border border-slate-200 border-l-4 border-l-[#DC143C] bg-white px-6 py-5 shadow-sm">
        <div>
            <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">Kontrol Persediaan</p>
            <h1 class="font-poppins text-2xl font-semibold">Stock Opname</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Pantau sesi hitung fisik, tindak lanjuti selisih, dan teruskan hasil untuk persetujuan.</p>
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

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Ringkasan siklus Stock Opname">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FFF1F0] text-[#DC143C]"><span class="material-symbols-outlined text-[25px]">inventory_2</span></span>
                <div><p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Ringkasan siklus</p><p class="mt-0.5 font-poppins text-lg font-semibold text-slate-900">{{ $stats['total'] }} sesi terpantau</p></div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">{{ $stats['counting'] }} sedang dihitung · {{ $stats['pending_approval'] }} menunggu review</span>
                <span class="inline-flex items-center gap-1.5 rounded-full {{ $activeSessionCount > 0 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }} px-3 py-1.5 text-xs font-semibold"><span class="h-1.5 w-1.5 rounded-full {{ $activeSessionCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>{{ $activeSessionCount }} perlu tindakan</span>
            </div>
        </div>
        <div class="px-6 pt-4"><div class="flex h-2 overflow-hidden rounded-full bg-slate-100" aria-label="Komposisi status sesi">
            @foreach($statusSummary as $item)
                @if($item['value'] > 0)<span class="{{ $item['bar_class'] }} transition-all duration-300" style="width: {{ ($item['value'] / $progressDenominator) * 100 }}%" title="{{ $item['label'] }}: {{ $item['value'] }}"></span>@endif
            @endforeach
        </div></div>
        <div class="grid gap-2 p-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($statusSummary as $item)
                <a href="{{ route('inventory.stock-opname.index', ['status' => $item['status']]) }}" class="group flex min-h-[76px] items-center justify-between gap-3 rounded-2xl border {{ $item['surface'] }} px-3.5 py-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                    <span class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $item['icon_class'] }}"><span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span></span><span class="min-w-0"><span class="block truncate text-xs font-semibold {{ $item['text_class'] }}">{{ $item['label'] }}</span><span class="mt-0.5 block text-[10px] text-slate-500">Lihat sesi</span></span></span>
                    <span class="font-poppins text-2xl font-semibold {{ $item['text_class'] }}">{{ $item['value'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('inventory.stock-opname.index') }}" data-auto-filter class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="min-w-[220px] flex-1">
            <label class="mb-1.5 block text-xs font-medium text-gray-600">Cari sesi</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-gray-400">search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nomor referensi Stock Opname" class="w-full rounded-xl border border-gray-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
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
                @unless(auth()->user()->isLocationScopedUser())<option value="">Semua lokasi</option>@endunless
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->filled('q') || request()->filled('status') || request()->filled('location_id'))
            <a href="{{ route('inventory.stock-opname.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50"><span class="material-symbols-outlined text-[16px]">close</span>Reset</a>
        @endif
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
        <div class="overflow-x-auto">
        <table class="w-full min-w-[920px] text-left text-sm">
            <thead class="bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Referensi</th>
                    <th class="px-6 py-3">Lokasi</th>
                    <th class="px-6 py-3">Jenis</th>
                    <th class="px-6 py-3">Petugas</th>
                    <th class="px-6 py-3">Snapshot</th>
                    <th class="px-6 py-3">Produk</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($opnames as $opname)
                @php
                    $st = $statusMap[$opname->status] ?? ['label' => $opname->status, 'class' => 'bg-gray-100 text-gray-700', 'icon' => 'help'];
                    $varianceCount = $opname->items->where('variance', '!=', 0)->count();
                @endphp
                <tr class="transition-colors hover:bg-rose-50/30">
                    <td class="border-l-2 {{ $st['border'] ?? 'border-l-slate-400' }} px-6 py-4">
                        <div class="flex flex-wrap items-center gap-2"><a href="{{ route('inventory.stock-opname.show', $opname) }}" class="font-semibold text-[#DC143C] hover:underline">{{ $opname->reference }}</a>@if(str_starts_with($opname->reference, 'DEMO-SO-'))<span class="rounded-full bg-violet-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-violet-700">Data demo</span>@endif</div>
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
                        {{ $opname->snapshot_at->format('d M Y') }}
                        <p class="text-xs text-gray-400">{{ $opname->snapshot_at->format('H:i') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $opname->items->count() }} produk</span>
                        @if($varianceCount > 0)<span class="mt-1 block text-[10px] font-medium text-amber-700">{{ $varianceCount }} selisih</span>@endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $st['class'] }}"><span class="material-symbols-outlined text-[14px]">{{ $st['icon'] }}</span>{{ $st['label'] }}</span>
                    </td>
                    <td class="px-6 py-4 text-right"><a href="{{ route('inventory.stock-opname.show', $opname) }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:border-primary/40 hover:text-primary">{{ $opname->status === 'counting' ? 'Lanjut hitung' : ($opname->status === 'pending_approval' && auth()->user()->hasPermissionTo('opname.approve') ? 'Tinjau' : 'Detail') }}<span class="material-symbols-outlined text-[14px]">arrow_forward</span></a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                        <span class="material-symbols-outlined text-[40px] block mb-2">inventory_2</span>
                        @if(request()->filled('q') || request()->filled('status') || request()->filled('location_id'))
                            Tidak ada sesi yang cocok dengan filter. <a href="{{ route('inventory.stock-opname.index') }}" class="font-semibold text-primary underline">Reset filter</a>
                        @else
                            Belum ada sesi Stock Opname.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="border-t border-gray-100 px-6 py-3">{{ $opnames->links() }}</div>
    </div>

</section>
@endsection
