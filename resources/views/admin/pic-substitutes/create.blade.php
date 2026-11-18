@extends('layouts.dashboard')
@php($pageTitle = 'Tambah PIC Pengganti')

@section('mobile-content')
<main class="space-y-4 p-4"><a href="{{ route('admin.pic-substitutes.index') }}" class="text-sm text-secondary">← Kembali</a><h1 class="text-xl font-semibold">Tambah PIC Pengganti</h1><form method="POST" action="{{ route('admin.pic-substitutes.store') }}" class="space-y-4 rounded-xl border bg-white p-4">@csrf
    <label class="block text-sm">Outlet<select name="location_id" required class="mt-1 w-full rounded border-gray-300"><option value="">Pilih outlet</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>@endforeach</select></label>
    <label class="block text-sm">PIC utama<select name="primary_pic_user_id" required class="mt-1 w-full rounded border-gray-300"><option value="">Pilih PIC utama</option>@foreach($primaryPics as $pic)<option value="{{ $pic->id }}" @selected(old('primary_pic_user_id') == $pic->id)>{{ $pic->name }} ({{ $pic->email }})</option>@endforeach</select><span class="mt-1 block text-xs text-secondary">PIC utama harus saat ini ditugaskan ke outlet di atas.</span></label>
    <label class="block text-sm">PIC pengganti<select name="substitute_pic_user_id" required class="mt-1 w-full rounded border-gray-300"><option value="">Pilih PIC pengganti</option>@foreach($substitutePics as $pic)<option value="{{ $pic->id }}" @selected(old('substitute_pic_user_id') == $pic->id)>{{ $pic->name }} ({{ $pic->email }})</option>@endforeach</select><span class="mt-1 block text-xs text-secondary">PIC pengganti tidak boleh memiliki jadwal pengganti lain yang bertumpang tindih.</span></label>
    <label class="block text-sm">Mulai<input type="datetime-local" name="starts_at" required min="{{ $minimumStart }}" value="{{ old('starts_at', $minimumStart) }}" class="mt-1 w-full rounded border-gray-300"></label>
    <label class="block text-sm">Berakhir<input type="datetime-local" name="ends_at" required value="{{ old('ends_at') }}" class="mt-1 w-full rounded border-gray-300"></label>
    <label class="block text-sm">Catatan (opsional)<textarea name="notes" maxlength="1000" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('notes') }}</textarea></label>
    @if($errors->any())<div class="rounded bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <button class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan jadwal PIC pengganti</button>
</form></main>
@endsection

@section('desktop-content')
<section class="mx-auto max-w-3xl space-y-5"><a href="{{ route('admin.pic-substitutes.index') }}" class="text-sm text-gray-500">← Kembali ke daftar</a><div><h1 class="text-2xl font-semibold">Tambah PIC Pengganti</h1><p class="mt-1 text-sm text-gray-500">Akses outlet otomatis berakhir sesuai jadwal. Pencabutan lebih awal memerlukan alasan dan masuk audit log.</p></div><form method="POST" action="{{ route('admin.pic-substitutes.store') }}" class="space-y-5 rounded-2xl border bg-white p-6">@csrf
    <div class="grid gap-4 md:grid-cols-2"><label class="text-sm font-medium">Outlet<select name="location_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Pilih outlet</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>@endforeach</select></label>
    <label class="text-sm font-medium">PIC utama<select name="primary_pic_user_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Pilih PIC utama</option>@foreach($primaryPics as $pic)<option value="{{ $pic->id }}" @selected(old('primary_pic_user_id') == $pic->id)>{{ $pic->name }} · {{ $pic->email }}</option>@endforeach</select><span class="mt-1 block text-xs text-gray-500">PIC utama harus aktif dan ditugaskan ke outlet ini.</span></label>
    <label class="text-sm font-medium">PIC pengganti<select name="substitute_pic_user_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Pilih PIC pengganti</option>@foreach($substitutePics as $pic)<option value="{{ $pic->id }}" @selected(old('substitute_pic_user_id') == $pic->id)>{{ $pic->name }} · {{ $pic->email }}</option>@endforeach</select><span class="mt-1 block text-xs text-gray-500">PIC pengganti hanya dapat ditugaskan ke satu outlet tambahan pada waktu yang sama.</span></label>
    <label class="text-sm font-medium">Mulai<input type="datetime-local" name="starts_at" required min="{{ $minimumStart }}" value="{{ old('starts_at', $minimumStart) }}" class="mt-1 block w-full rounded-lg border-gray-300"></label>
    <label class="text-sm font-medium">Berakhir<input type="datetime-local" name="ends_at" required value="{{ old('ends_at') }}" class="mt-1 block w-full rounded-lg border-gray-300"></label></div>
    <label class="block text-sm font-medium">Catatan (opsional)<textarea name="notes" maxlength="1000" rows="3" class="mt-1 block w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea></label>
    @if($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <div class="flex justify-end"><button class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan jadwal</button></div>
</form></section>
@endsection
