<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'invoice.view' => ['View supplier purchase invoices', ['super_admin', 'purchasing', 'finance']],
        'invoice.manage' => ['Create and submit supplier purchase invoices', ['super_admin', 'purchasing']],
        'invoice.review' => ['Review supplier purchase invoices and view inventory quantities for valuation', ['super_admin', 'finance']],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name => [$label, $roles]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['label' => $label, 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            $roleIds = DB::table('roles')->whereIn('name', $roles)->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->permissions) as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            if ($permissionId) {
                DB::table('permission_role')->where('permission_id', $permissionId)->delete();
                DB::table('permissions')->where('id', $permissionId)->delete();
            }
        }
    }
};
