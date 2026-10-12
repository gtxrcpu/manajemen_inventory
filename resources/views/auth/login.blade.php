@extends('layouts.app')

@section('title', 'Masuk ke Sistem Inventory - Technolife')

@section('content')
<main class="min-h-screen w-full flex flex-col items-center justify-center p-4 relative font-inter" style="background-image: url('{{ asset('login.webp') }}'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;">
    <!-- Centered Login Card -->
    <article class="w-full max-w-[440px] bg-white border border-[#EAEAEA] rounded-2xl p-8 md:p-10 shadow-md transition-all relative z-10">
        <!-- Header Inside Card -->
        <header class="text-center">
            <!-- Logo -->
            <div class="mb-4">
                <a href="{{ route('login') }}" class="inline-flex items-center font-poppins font-semibold text-2xl tracking-tight text-[#1B1C1C]" aria-label="Technolife">
                    Technolife<span class="text-[#DC143C] ml-0.5 leading-none">•</span>
                </a>
            </div>

            <!-- Judul Utama -->
            <h1 class="font-poppins font-semibold text-xl text-[#1B1C1C] text-center mb-1">
                Masuk ke Sistem Inventory
            </h1>

            <!-- Subtitle -->
            <p class="text-xs text-[#5C3F3F] text-center mb-6">
                Masuk dulu, ya. Gunakan email operasional yang terdaftar.
            </p>
        </header>

        <!-- Validation Error Message -->
        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-600 flex items-start gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div class="flex-1">
                    <p class="font-medium">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <!-- Form Fields -->
        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <!-- Input 1: ID Staf / Email Bisnis -->
            <div>
                <label for="email" class="text-xs font-semibold text-[#1B1C1C] mb-1.5 block">
                    Email Operasional
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-400 pointer-events-none flex items-center justify-center">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </span>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@technolife.id"
                        class="w-full bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl pl-10 pr-4 py-3 text-sm text-[#1B1C1C] focus:outline-none focus:border-[#DC143C] placeholder:text-slate-400 transition-colors"
                    >
                </div>
            </div>

            <!-- Input 2: Kata Sandi -->
            <div>
                <label for="password" class="text-xs font-semibold text-[#1B1C1C] mb-1.5 block">
                    Kata Sandi
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-400 pointer-events-none flex items-center justify-center">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi Anda"
                        class="w-full bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl pl-10 pr-10 py-3 text-sm text-[#1B1C1C] focus:outline-none focus:border-[#DC143C] placeholder:text-slate-400 transition-colors"
                    >
                    <button
                        type="button"
                        id="togglePassword"
                        aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute right-3.5 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer flex items-center justify-center p-0.5"
                    >
                        <svg id="eyeIcon" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="eyeOffIcon" class="w-4 h-4 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-.722-3.25"/>
                            <path d="M2 2l20 20"/>
                            <path d="M8.71 8.71a4 4 0 0 0 5.58 5.58"/>
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Row Opsi Tambahan (Flex Between) -->
            <div class="flex items-center justify-between pt-1">
                <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        class="w-4 h-4 rounded border-[#EAEAEA] text-[#DC143C] accent-[#DC143C] focus:ring-[#DC143C] cursor-pointer"
                    >
                    <span class="text-xs text-[#5C3F3F]">Ingat saya di perangkat ini</span>
                </label>
                <a href="#" class="text-xs font-semibold text-[#DC143C] hover:underline transition-colors">
                    Lupa Kata Sandi?
                </a>
            </div>

            <!-- Tombol Masuk (Primary CTA) -->
            <button
                type="submit"
                class="group w-full bg-[#DC143C] hover:bg-[#B1002C] text-white font-semibold py-3.5 rounded-xl shadow-xs transition-colors flex items-center justify-center gap-2 mt-2 cursor-pointer text-sm"
            >
                <span>Masuk ke Dashboard</span>
                <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/>
                    <path d="m12 5 7 7-7 7"/>
                </svg>
            </button>
        </form>
    </article>

    <!-- Bottom Security Badge -->
    <aside class="mt-6 flex items-center justify-center gap-1.5 text-[11px] text-slate-500 bg-white/80 backdrop-blur-xs px-4 py-1.5 rounded-full border border-slate-200/60 shadow-xs select-none max-w-full text-center">
        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span>Akses Terenkripsi SSL 256-Bit • Khusus Staf Operasional Technolife</span>
    </aside>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeOffIcon = document.getElementById('eyeOffIcon');

        if (toggleBtn && passwordInput && eyeIcon && eyeOffIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeIcon.classList.toggle('hidden', isPassword);
                eyeOffIcon.classList.toggle('hidden', !isPassword);
            });
        }
    });
</script>
@endsection
