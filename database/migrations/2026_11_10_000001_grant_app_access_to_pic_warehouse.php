<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'app.access'],
            ['label' => 'Access application pages', 'updated_at' => $now, 'created_at' => $now],
        );

        $permissionId = DB::table('permissions')->where('name', 'app.access')->value('id');
        $roleId = DB::table('roles')->where('name', 'pic_warehouse')->value('id');

        if ($permissionId && $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                [],
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'app.access')->value('id');
        $roleId = DB::table('roles')->where('name', 'pic_warehouse')->value('id');

        if ($permissionId && $roleId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->where('role_id', $roleId)->delete();
        }
    }
};
