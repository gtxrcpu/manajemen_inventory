<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('roles')->updateOrInsert(
            ['name' => 'staff_outlet'],
            ['label' => 'Staff Outlet', 'updated_at' => $now, 'created_at' => $now],
        );
        $roleId = DB::table('roles')->where('name', 'staff_outlet')->value('id');

        $permissions = [
            'app.access' => 'Access application pages',
            'reports.view' => 'View inventory and operational reports',
            'low_stock.view' => 'View low stock alerts and history',
            'opname.manage' => 'Create and count stock opname',
            'transfer.receive' => 'Receive stock transfers at outlets',
        ];
        foreach ($permissions as $permissionName => $label) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permissionName],
                ['label' => $label, 'updated_at' => $now, 'created_at' => $now],
            );
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => DB::table('permissions')->where('name', $permissionName)->value('id'),
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'staff_outlet')->value('id');
        if ($roleId) {
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
