@extends('layouts.dashboard')
@php($pageTitle = 'Pengaturan Sistem')

@section('mobile-content')
<main class="w-full space-y-4 p-4">
    <header>
        <h1 class="font-poppins text-xl font-semibold text-gray-900">Pengaturan Sistem</h1>
        <p class="mt-1 text-xs leading-5 text-gray-500">Pusat akses pengaturan aplikasi. Hak akses tiap pengaturan tetap mengikuti permission terkait.</p>
    </header>

    <div class="space-y-3">
        @foreach($configurationGroups as $group)
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined rounded-lg bg-gray-100 p-2 text-gray-700">{{ $group['icon'] }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-gray-900">{{ $group['title'] }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $group['statusClass'] }}">{{ $group['status'] }}</span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-gray-500">{{ $group['description'] }}</p>
                    </div>
                </div>
                <ul class="mt-3 space-y-1.5 border-t border-gray-100 pt-3">
                    @foreach($group['items'] as $item)
                        <li class="flex items-start gap-2 text-xs text-gray-600"><span class="material-symbols-outlined text-sm text-gray-400">check</span>{{ $item }}</li>
                    @endforeach
                </ul>
                @if($group['route'])
                    <a href="{{ $group['route'] }}" class="mt-4 flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        {{ $group['action'] }}<span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </a>
                @else
                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800">Nilai ini belum bisa diubah sampai aturan bisnis disepakati.</p>
                @endif
            </article>
        @endforeach
    </div>
</main>
@endsection

@section('desktop-content')
<section class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Administrasi</p>
            <h1 class="mt-1 font-poppins text-2xl font-semibold text-gray-900">Pengaturan Sistem</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Pusat akses konfigurasi. Setiap halaman tujuan tetap menerapkan permission masing-masing.</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"><span class="material-symbols-outlined text-base">verified_user</span>Super Admin</span>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        @foreach($configurationGroups as $group)
            <article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined rounded-xl bg-gray-100 p-3 text-gray-700">{{ $group['icon'] }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-poppins text-lg font-semibold text-gray-900">{{ $group['title'] }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $group['statusClass'] }}">{{ $group['status'] }}</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-gray-500">{{ $group['description'] }}</p>
                    </div>
                </div>
                <ul class="mt-5 flex-1 space-y-2 border-t border-gray-100 pt-4">
                    @foreach($group['items'] as $item)
                        <li class="flex items-start gap-2 text-sm text-gray-600"><span class="material-symbols-outlined text-lg text-green-600">check_circle</span>{{ $item }}</li>
                    @endforeach
                </ul>
                @if($group['route'])
                    <a href="{{ $group['route'] }}" class="mt-5 inline-flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 transition hover:border-gray-300 hover:bg-gray-50">
                        {{ $group['action'] }}<span class="material-symbols-outlined text-lg">arrow_forward</span>
                    </a>
                @else
                    <p class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">Nilai ini belum bisa diubah sampai aturan bisnis disepakati.</p>
                @endif
            </article>
        @endforeach
    </div>
</section>
@endsection
