<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permissionName = 'purchase-request.approve';

    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permissionName],
            ['label' => 'Review and approve purchase requests', 'created_at' => now(), 'updated_at' => now()],
        );

        $permissionId = DB::table('permissions')->where('name', $this->permissionName)->value('id');
        $roleIds = DB::table('roles')->whereIn('name', ['super_admin', 'management'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', $this->permissionName)->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
