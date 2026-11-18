<div>
    <label for="name" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama</label>
    <input id="name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="255" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 text-sm outline-none focus:border-primary">
</div>
<div>
    <label for="email" class="mb-1.5 block text-sm font-semibold text-on-surface">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required maxlength="255" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 text-sm outline-none focus:border-primary">
</div>
<div>
    <label for="whatsapp_phone" class="mb-1.5 block text-sm font-semibold text-on-surface">Nomor WhatsApp PIC (opsional)</label>
    <input id="whatsapp_phone" name="whatsapp_phone" value="{{ old('whatsapp_phone', $user?->whatsapp_phone) }}" inputmode="tel" maxlength="24" placeholder="6281234567890" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 text-sm outline-none focus:border-primary">
    <p class="mt-1 text-xs text-secondary">Staff Gudang menerima info supplier untuk gudang penugasannya; Staff Outlet dan PIC Outlet menerima alert stok minimum outlet. PIC Gudang menerima permintaan persetujuan transfer. Gunakan kode negara, misalnya 62.</p>
</div>
<div>
    <label for="password" class="mb-1.5 block text-sm font-semibold text-on-surface">{{ $user ? 'Password baru (opsional)' : 'Password' }}</label>
    <div class="relative">
        <input id="password" name="password" type="password" {{ $user ? '' : 'required' }} autocomplete="new-password" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 pr-12 text-sm outline-none focus:border-primary">
        <button type="button" onclick="togglePasswordVisibility('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors">
            <span class="material-symbols-outlined text-[20px]" id="password-icon">visibility_off</span>
        </button>
    </div>
</div>
<div>
    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-on-surface">Konfirmasi password</label>
    <div class="relative">
        <input id="password_confirmation" name="password_confirmation" type="password" {{ $user ? '' : 'required' }} autocomplete="new-password" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 pr-12 text-sm outline-none focus:border-primary">
        <button type="button" onclick="togglePasswordVisibility('password_confirmation')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors">
            <span class="material-symbols-outlined text-[20px]" id="password_confirmation-icon">visibility_off</span>
        </button>
    </div>
</div>
<fieldset>
    <legend class="mb-2 text-sm font-semibold text-on-surface">Role</legend>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ($roles as $role)
            <label class="flex items-center gap-2 rounded-xl border border-outline px-3 py-3 text-sm text-on-surface">
                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $user?->roles->pluck('id')->all() ?? []))) class="h-4 w-4 rounded border-outline text-primary focus:ring-primary">
                {{ $role->label }}
            </label>
        @endforeach
    </div>
</fieldset>
<div>
    <label for="location_id" class="mb-1.5 block text-sm font-semibold text-on-surface">Penempatan Lokasi <span class="font-normal text-secondary">(wajib untuk Staff Outlet, Staff Gudang, dan semua PIC)</span></label>
    <select id="location_id" name="location_id" class="w-full rounded-xl border border-outline bg-surface px-3.5 py-3 text-sm outline-none focus:border-primary">
        <option value="">{{ $locations->isEmpty() ? 'Belum ada lokasi aktif' : 'Pilih lokasi' }}</option>
        @foreach ($locations as $location)
            <option value="{{ $location->id }}" @selected((string) old('location_id', $user?->location_id) === (string) $location->id)>{{ $location->type === 'warehouse' ? 'Gudang · ' : 'Outlet · ' }}{{ $location->name }}</option>
        @endforeach
    </select>
    @if ($locations->isEmpty())
        <p class="mt-2 text-xs text-amber-700">Belum ada lokasi Outlet atau Gudang yang aktif. Tambahkan lokasi melalui <a class="font-semibold underline" href="{{ route('admin.master-data.index', 'locations') }}">Master Data Lokasi</a>.</p>
    @endif
</div>
