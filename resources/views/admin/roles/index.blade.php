@extends('layouts.app')

@section('title', 'Role & Permission - Technolife Inventory')

@section('content')
<main class="min-h-screen bg-surface px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-primary hover:underline">&larr; Pengguna</a>
                <h1 class="mt-2 font-poppins text-2xl font-semibold text-on-surface">Role & Permission</h1>
                <p class="mt-1 text-sm text-secondary">Atur akses halaman untuk setiap role aplikasi.</p>
            </div>
        </header>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="space-y-4">
            @foreach ($roles as $role)
                <section class="rounded-2xl border border-outline bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-48">
                            <h2 class="font-poppins text-lg font-semibold text-on-surface">{{ $role->label }}</h2>
                            <p class="mt-1 text-xs text-secondary">{{ $role->users()->count() }} pengguna</p>
                            @if ($role->name === 'super_admin')
                                <span class="mt-3 inline-flex rounded-full bg-primary-container px-2.5 py-1 text-xs font-semibold text-primary">users.manage tetap khusus Super Admin</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <fieldset>
                                <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-secondary">Permission halaman</legend>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center gap-2 rounded-xl border border-outline px-3 py-2.5 text-sm text-on-surface">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id)) class="h-4 w-4 rounded border-outline text-primary focus:ring-primary">
                                            <span>{{ $permission->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div class="mt-4 flex justify-end">
                                <button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#950025]">Simpan Permission</button>
                            </div>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>

        <p class="text-xs leading-5 text-secondary">Permission administrasi user dikunci untuk Super Admin. Mengubah permission akses halaman dapat memengaruhi kemampuan role membuka Dashboard dan Data Stock.</p>
    </div>
</main>
@endsection
