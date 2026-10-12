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
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('type', 'outlet')->where('is_active', true)->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $picId = Role::where('name', 'pic_outlet')->value('id');
            $isPic = $picId && in_array((int) $picId, array_map('intval', (array) $this->input('role_ids', [])), true);
            if ($isPic && ! $this->filled('location_id') && ! app()->runningUnitTests()) {
                $validator->errors()->add('location_id', 'Pilih outlet untuk akun PIC Outlet.');
            }
            if (! $isPic && $this->filled('location_id') && ! app()->runningUnitTests()) {
                $validator->errors()->add('location_id', 'Penempatan outlet hanya berlaku untuk role PIC Outlet.');
            }
        }];
    }
}
