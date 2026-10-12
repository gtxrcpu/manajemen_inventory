@extends('layouts.app')

@section('title', 'Tambah Pengguna - Technolife Inventory')

@section('content')
<main class="min-h-screen bg-surface px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-primary hover:underline">&larr; Kembali ke Pengguna</a>
        <h1 class="mt-4 font-poppins text-2xl font-semibold text-on-surface">Tambah Pengguna</h1>
        <p class="mt-1 text-sm text-secondary">Buat akun dan tetapkan satu atau beberapa role.</p>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 space-y-5 rounded-2xl border border-outline bg-white p-5 shadow-sm sm:p-7">
            @csrf
            @include('admin.users._form', ['user' => null, 'roles' => $roles])
            <div class="flex justify-end gap-2 border-t border-outline pt-5">
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-outline px-4 py-2.5 text-sm font-semibold text-on-surface">Batal</a>
                <button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#950025]">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</main>

<script>
function togglePasswordVisibility(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility_off';
    }
}
</script>
@endsection
