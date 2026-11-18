<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->grant('disposal.manage', 'Submit stock write-off and supplier return requests', ['super_admin', 'staff_gudang']);
        $this->grant('disposal.approve', 'Review stock write-off and supplier return requests', ['super_admin', 'purchasing']);
    }

    private function grant(string $name, string $label, array $roles): void
    {
        $permissionId = DB::table('permissions')->where('name', $name)->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId(['name' => $name, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('roles')->whereIn('name', $roles)->pluck('id') as $roleId) {
            DB::table('permission_role')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId], []);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['disposal.manage', 'disposal.approve'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
