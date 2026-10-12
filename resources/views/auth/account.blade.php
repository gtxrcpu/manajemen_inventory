@extends('layouts.app')

@section('title', 'Akun - Technolife Inventory')

@section('content')
<main class="min-h-screen flex items-center justify-center bg-surface px-4 py-10">
    <section class="w-full max-w-lg rounded-2xl border border-outline bg-white p-7 shadow-[0_8px_28px_rgba(0,0,0,0.07)] sm:p-9">
        <p class="font-poppins text-sm font-semibold text-primary">Technolife Inventory</p>
        <h1 class="mt-5 font-poppins text-2xl font-semibold text-on-surface">Akun Anda</h1>

        <dl class="mt-6 divide-y divide-outline rounded-xl border border-outline px-4">
            <div class="py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-secondary">Nama</dt>
                <dd class="mt-1 text-sm text-on-surface">{{ auth()->user()->name }}</dd>
            </div>
            <div class="py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-secondary">Email</dt>
                <dd class="mt-1 text-sm text-on-surface">{{ auth()->user()->email }}</dd>
            </div>
        </dl>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-outline px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-low">Kembali ke Dashboard</a>
            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                @if (auth()->user()->hasPermissionTo('users.manage'))
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-low">Kelola Pengguna</a>
                    <a href="{{ route('admin.master-data.index', 'products') }}" class="inline-flex items-center justify-center rounded-xl border border-outline px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-low">Master Data</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-on-primary transition hover:bg-[#950025] sm:w-auto">Keluar</button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
