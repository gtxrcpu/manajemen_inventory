<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->whereIn('type', ['outlet', 'warehouse'])->where('is_active', true)->whereNull('deleted_at')],
            'whatsapp_phone' => ['nullable', 'string', 'max:24', 'regex:/^\+?[0-9][0-9\s().-]{7,22}$/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $roleNames = Role::whereIn('id', array_map('intval', (array) $this->input('role_ids', [])))->pluck('name');
            $isOutletPic = $roleNames->contains('pic_outlet');
            $isWarehousePic = $roleNames->contains('pic_warehouse');
            $isOutletStaff = $roleNames->contains('staff_outlet');
            $isWarehouseStaff = $roleNames->contains('staff_gudang');
            if ($isOutletStaff && $roleNames->count() > 1) {
                $validator->errors()->add('role_ids', 'Staff Outlet harus menjadi role tunggal agar aksesnya tetap terbatas pada outlet penugasannya.');
            }
            if ($isWarehouseStaff && $roleNames->count() > 1) {
                $validator->errors()->add('role_ids', 'Staff Gudang harus menjadi role tunggal agar aksesnya tetap terbatas pada gudang penugasannya.');
            }
            if (($isOutletPic && $isWarehousePic) || ($isOutletStaff && ($isOutletPic || $isWarehousePic))) {
                $validator->errors()->add('role_ids', 'Pilih satu peran lokasi: Staff Outlet, PIC Outlet, atau PIC Gudang.');
            }
            if (($isWarehousePic || $isOutletStaff) && $roleNames->contains('staff_gudang')) {
                $validator->errors()->add('role_ids', $isWarehousePic
                    ? 'PIC Gudang harus terpisah dari Staff Gudang agar persetujuan transfer independen.'
                    : 'Staff Outlet tidak dapat dirangkap sebagai Staff Gudang.');
            }
            if (($isOutletPic || $isWarehousePic || $isOutletStaff || $isWarehouseStaff) && ! $this->filled('location_id')) {
                $locationLabel = ($isWarehousePic || $isWarehouseStaff) ? 'gudang' : 'outlet';
                $validator->errors()->add('location_id', 'Pilih '.$locationLabel.' untuk akun operasional.');
            }
            if (! $isOutletPic && ! $isWarehousePic && ! $isOutletStaff && ! $isWarehouseStaff && $this->filled('location_id')) {
                $validator->errors()->add('location_id', 'Penempatan lokasi hanya berlaku untuk Staff Outlet, Staff Gudang, atau akun PIC.');
            }
            if ($this->filled('location_id') && ($isOutletPic || $isWarehousePic || $isOutletStaff || $isWarehouseStaff)) {
                $expectedType = ($isWarehousePic || $isWarehouseStaff) ? 'warehouse' : 'outlet';
                if (\App\Models\Location::whereKey($this->input('location_id'))->value('type') !== $expectedType) {
                    $validator->errors()->add('location_id', $expectedType === 'warehouse' ? 'Staff/PIC Gudang harus ditempatkan pada lokasi Gudang.' : 'Staff/PIC Outlet harus ditempatkan pada lokasi Outlet.');
                }
            }
            if ($this->filled('whatsapp_phone') && ! preg_match('/^[1-9][0-9]{7,14}$/', preg_replace('/\D+/', '', $this->input('whatsapp_phone')))) {
                $validator->errors()->add('whatsapp_phone', 'Gunakan nomor internasional 8–15 digit, misalnya 6281234567890.');
            }
        }];
    }
}
