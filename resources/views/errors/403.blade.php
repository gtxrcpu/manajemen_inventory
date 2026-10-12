@extends('layouts.app')

@section('title', 'Akses Ditolak - Technolife Inventory')

@section('content')
<main class="min-h-screen flex items-center justify-center bg-surface px-4 py-10">
    <section class="w-full max-w-lg rounded-2xl border border-outline bg-white p-8 text-center shadow-[0_8px_28px_rgba(0,0,0,0.07)]">
        <p class="font-plex-mono text-sm font-semibold text-primary">403</p>
        <h1 class="mt-3 font-poppins text-2xl font-semibold text-on-surface">Akses tidak tersedia</h1>
        <p class="mt-2 text-sm leading-6 text-secondary">Akun Anda belum memiliki izin untuk membuka halaman ini. Hubungi administrator jika Anda memerlukan akses.</p>
        <p class="mt-6 text-xs text-secondary">403 · Forbidden</p>
    </section>
</main>
@endsection
