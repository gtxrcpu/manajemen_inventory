<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'opname.manage')->value('id');
        $roleId = DB::table('roles')->where('name', 'pic_outlet')->value('id');

        if ($permissionId && $roleId) {
            DB::table('permission_role')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ], []);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'opname.manage')->value('id');
        $roleId = DB::table('roles')->where('name', 'pic_outlet')->value('id');

        if ($permissionId && $roleId) {
            DB::table('permission_role')->where([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ])->delete();
        }
    }
};
