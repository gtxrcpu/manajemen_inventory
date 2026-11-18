@extends('layouts.dashboard')

@php $pageTitle = 'Pengguna — Technolife'; @endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="font-poppins text-lg font-semibold text-on-surface">Pengguna</h1>
            <p class="text-xs text-secondary mt-0.5">Kelola akun dan role akses</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="{{ route('admin.pic-substitutes.index') }}" class="h-9 rounded-xl border border-outline px-2.5 text-[11px] font-semibold flex items-center">PIC Pengganti</a>
            <a href="{{ route('admin.users.create') }}" class="h-9 rounded-xl bg-primary px-3.5 text-xs font-semibold text-on-primary flex items-center gap-1.5 shadow-sm"><span class="material-symbols-outlined text-[16px]">add</span>Tambah</a>
        </div>
    </div>

    <!-- Flash -->
    @if(session('status'))
    <div id="flash-m" class="rounded-xl border border-green-200 bg-green-50 p-3 flex items-center justify-between gap-2 text-sm text-green-800">
        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-green-500 text-[18px] shrink-0">check_circle</span>{{ session('status') }}</div>
        <button onclick="document.getElementById('flash-m').remove()" class="text-green-500 shrink-0"><span class="material-symbols-outlined text-[18px]">close</span></button>
    </div>
    @endif
    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 flex items-center gap-2">
        <span class="material-symbols-outlined text-red-500 text-[18px] shrink-0">error</span>{{ $errors->first() }}
    </div>
    @endif

    <!-- Status Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 scrollbar-none">
        @foreach(['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'deleted' => 'Terhapus'] as $key => $label)
            <a href="{{ route('admin.users.index', ['status' => $key, 'q' => request('q')]) }}"
               class="whitespace-nowrap px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors shrink-0
                      {{ $status === $key ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container text-secondary hover:bg-surface-variant' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Search -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-secondary">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </div>
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
                class="w-full pl-9 pr-4 h-10 bg-surface-container-lowest border border-outline rounded-xl text-sm text-on-surface placeholder:text-secondary/60 focus:border-primary focus:ring-1 focus:ring-primary/20 outline-none transition-all">
        </div>
        <button type="submit" class="h-10 px-4 bg-primary text-on-primary text-xs font-semibold rounded-xl shrink-0">Cari</button>
    </form>

    <!-- User Cards -->
    @forelse($users as $user)
    <div class="bg-surface-container-lowest rounded-2xl border border-outline shadow-xs overflow-hidden">
        <div class="flex items-center gap-3 p-3.5">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-on-primary text-[20px]">person</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-semibold text-on-surface truncate">{{ $user->name }}</p>
                    @if($user->trashed())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 shrink-0">Terhapus</span>
                    @elseif($user->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700 shrink-0">Aktif</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 shrink-0">Nonaktif</span>
                    @endif
                </div>
                <p class="text-xs text-secondary truncate mt-0.5">{{ $user->email }}</p>
                @if($user->location)<p class="text-[11px] text-secondary mt-0.5">{{ $user->location->type === 'warehouse' ? 'Gudang' : 'Outlet' }}: {{ $user->location->name }}</p>@endif
                <div class="flex flex-wrap gap-1 mt-1.5">
                    @forelse($user->roles as $role)
                        <span class="px-2 py-0.5 rounded-full bg-surface-container text-secondary text-[10px] font-medium">{{ $role->label }}</span>
                    @empty
                        <span class="text-[11px] text-secondary">Belum ada role</span>
                    @endforelse
                </div>
            </div>
        </div>
        <!-- Actions -->
        <div class="flex border-t border-outline divide-x divide-outline">
            @if($user->trashed())
                <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <button class="w-full py-2.5 text-xs font-semibold text-green-700 hover:bg-green-50 transition-colors">Pulihkan</button>
                </form>
            @else
                <a href="{{ route('admin.users.edit', $user) }}" class="flex-1 py-2.5 text-xs font-semibold text-secondary text-center hover:bg-surface-container-low transition-colors">Ubah</a>
                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <button class="w-full py-2.5 text-xs font-semibold {{ $user->is_active ? 'text-amber-700' : 'text-green-700' }} hover:bg-surface-container-low transition-colors">
                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="flex-1"
                      data-confirm-title="Hapus akun ini?"
                      data-confirm-message="Akun akan dipindahkan ke daftar terhapus dan tidak dapat digunakan untuk masuk."
                      data-confirm-button="Ya, hapus akun" data-confirm-tone="danger">
                    @csrf @method('DELETE')
                    <button class="w-full py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">Hapus</button>
                </form>
            @endif
        </div>
    </div>
    @empty
        <div class="rounded-2xl border border-outline bg-white p-8 text-center">
            <span class="material-symbols-outlined text-[40px] text-secondary/40 block mb-2">group</span>
            <p class="font-semibold text-on-surface text-sm">Belum ada pengguna</p>
            <p class="text-xs text-secondary mt-1">Pada filter ini tidak ada pengguna ditemukan.</p>
        </div>
    @endforelse

    @if($users->hasPages())
        <div class="pt-1">{{ $users->links() }}</div>
    @endif

    <!-- Role & Permission link -->
    <a href="{{ route('admin.roles.index') }}"
       class="w-full h-11 border border-outline rounded-xl flex items-center justify-center gap-2 text-sm font-semibold text-secondary hover:bg-surface-container-low transition-colors">
        <span class="material-symbols-outlined text-[18px]">admin_panel_settings</span>Role &amp; Permission
    </a>

</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 min-w-0">
        <div>
            <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">Pengguna</h1>
            <p class="text-xs text-gray-500 mt-0.5">Kelola akun pengguna dan hak akses aplikasi.</p>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 border border-[#EAEAEA] rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>Role &amp; Permission
            </a>
            <a href="{{ route('admin.pic-substitutes.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-[#EAEAEA] px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                <span class="material-symbols-outlined text-[16px]">group</span>PIC Pengganti
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#DC143C] text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-[#c01230] transition-colors">
                <span class="material-symbols-outlined text-[18px]">add</span>Tambah Pengguna
            </a>
        </div>
    </div>

    <!-- Flash -->
    @if(session('status'))
    <div id="flash-d" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 flex items-center justify-between gap-3 text-sm text-green-800">
        <div class="flex items-center gap-3"><span class="material-symbols-outlined text-green-500 text-[18px] shrink-0">check_circle</span>{{ session('status') }}</div>
        <button onclick="document.getElementById('flash-d').remove()" class="text-green-400 hover:text-green-600 transition-colors shrink-0"><span class="material-symbols-outlined text-[18px]">close</span></button>
    </div>
    @endif
    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex items-center gap-3 text-sm text-red-700">
        <span class="material-symbols-outlined text-red-500 text-[18px] shrink-0">error</span>{{ $errors->first() }}
    </div>
    @endif

    <!-- Users Table Card -->
    <section class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden min-w-0">

        <!-- Filter Bar -->
        <div class="px-6 py-4 border-b border-[#EAEAEA]">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <!-- Status tabs -->
                <nav class="flex items-center gap-1 p-1 bg-[#F5F5FA] rounded-xl">
                    @foreach(['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'deleted' => 'Terhapus'] as $key => $label)
                        <a href="{{ route('admin.users.index', ['status' => $key, 'q' => request('q')]) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all
                                  {{ $status === $key ? 'bg-white text-[#DC143C] shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white/60' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
                <!-- Search -->
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
                            class="w-64 pl-8 pr-4 h-9 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all">
                    </div>
                    <button type="submit" class="h-9 px-4 bg-[#DC143C] text-white text-xs font-semibold rounded-lg hover:bg-[#c01230] transition-colors">Cari</button>
                </form>
            </div>
        </div>

        <!-- Table -->
        @if($users->isEmpty())
            <div class="py-16 text-center">
                <span class="material-symbols-outlined text-[48px] text-gray-200 block mb-3">group</span>
                <p class="font-semibold text-gray-700">Tidak ada pengguna ditemukan</p>
                <p class="mt-1 text-sm text-gray-400">Coba ubah filter atau kata kunci pencarian.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[640px]">
                    <thead>
                        <tr class="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-6">Pengguna</th>
                            <th class="py-3 px-4">Role</th>
                            <th class="py-3 px-4 w-28 text-center">Status</th>
                            <th class="py-3 px-4 w-48 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAEAEA]">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <!-- Avatar + Name -->
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#FFF1F0] flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[#DC143C] text-[16px]">person</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</div>
                                        @if($user->location)<div class="text-[11px] text-gray-500 mt-0.5">{{ $user->location->type === 'warehouse' ? 'Gudang' : 'Outlet' }}: {{ $user->location->name }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <!-- Roles -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#F5F5FA] text-gray-600">{{ $role->label }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">Belum ada role</span>
                                    @endforelse
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($user->trashed())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Terhapus</span>
                                @elseif($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]"><span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>Aktif</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Nonaktif</span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($user->trashed())
                                        <form method="POST" action="{{ route('admin.users.restore', $user->id) }}">
                                            @csrf @method('PATCH')
                                            <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-[11px] font-semibold hover:bg-green-100 transition-colors">
                                                <span class="material-symbols-outlined text-[13px]">restore</span>Pulihkan
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#EAEAEA] text-gray-600 text-[11px] font-semibold hover:bg-gray-50 transition-colors">
                                            <span class="material-symbols-outlined text-[13px]">edit</span>Ubah
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                            @csrf @method('PATCH')
                                            <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#EAEAEA] text-[11px] font-semibold transition-colors
                                                           {{ $user->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-green-700 hover:bg-green-50' }}">
                                                <span class="material-symbols-outlined text-[13px]">{{ $user->is_active ? 'block' : 'check_circle' }}</span>
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              data-confirm-title="Hapus akun ini?"
                                              data-confirm-message="Akun akan dipindahkan ke daftar terhapus dan tidak dapat digunakan untuk masuk."
                                              data-confirm-button="Ya, hapus akun" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#FFF1F0] text-[#DC143C] text-[11px] font-semibold hover:bg-[#FFE4E1] transition-colors">
                                                <span class="material-symbols-outlined text-[13px]">delete</span>Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-[#EAEAEA] flex items-center justify-between gap-4 text-xs text-gray-500">
                <div>Menampilkan <span class="font-semibold text-gray-700">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span> dari <span class="font-semibold text-gray-700">{{ $users->total() }}</span> pengguna</div>
                {{ $users->links() }}
            </div>
        @endif
    </section>

    <!-- Audit Log Card -->
    <section class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden">
        <div class="px-6 py-4 border-b border-[#EAEAEA] flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#FFF1F0] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#DC143C] text-[18px]">history</span>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Audit Log Perubahan</h2>
                <p class="text-xs text-gray-400 mt-0.5">Aktivitas terbaru dicatat bersama pelaku dan waktu perubahan</p>
            </div>
        </div>
        @forelse($auditLogs as $log)
        <div class="flex items-center justify-between px-6 py-3.5 border-b border-[#EAEAEA] last:border-0 hover:bg-gray-50/50 transition-colors">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-7 h-7 rounded-full bg-[#F5F5FA] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-gray-400 text-[14px]">edit_note</span>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-gray-900">{{ $log->action }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                </div>
            </div>
            <div class="text-xs text-gray-400 shrink-0 ml-4">
                <span class="font-medium text-gray-600">{{ $log->actor?->name ?? 'Akun dihapus' }}</span>
                · {{ $log->created_at->format('d M Y H:i') }}
            </div>
        </div>
        @empty
        <div class="px-6 py-10 text-center">
            <span class="material-symbols-outlined text-[40px] text-gray-200 block mb-2">history</span>
            <p class="text-sm text-gray-400">Belum ada perubahan yang dicatat.</p>
        </div>
        @endforelse
    </section>

@endsection

@section('page-scripts')
    // Live search filter
    (function() {
        const input = document.getElementById('d-search-input');
        if (!input) return;
        input.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = (q === '' || row.innerText.toLowerCase().includes(q)) ? '' : 'none';
            });
        });
    })();
@endsection
