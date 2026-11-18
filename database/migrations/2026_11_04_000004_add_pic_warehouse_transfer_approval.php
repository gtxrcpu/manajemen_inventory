<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->timestamp('dispatched_at')->nullable()->change();
            $table->foreignId('dispatched_by')->nullable()->change();
            $table->foreignId('prepared_by')->nullable()->after('dispatched_by')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('prepared_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
        DB::table('stock_transfers')->whereNull('prepared_by')->update(['prepared_by' => DB::raw('dispatched_by')]);

        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->boolean('transfer_approval_enabled')->default(false)->after('supplier_arrival_enabled');
        });

        $now = now();
        DB::table('roles')->updateOrInsert(['name' => 'pic_warehouse'], ['label' => 'PIC Gudang', 'updated_at' => $now, 'created_at' => $now]);
        $warehousePicRoleId = DB::table('roles')->where('name', 'pic_warehouse')->value('id');
        $appAccessId = DB::table('permissions')->where('name', 'app.access')->value('id');
        if ($appAccessId) {
            DB::table('permission_role')->updateOrInsert(['permission_id' => $appAccessId, 'role_id' => $warehousePicRoleId]);
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'transfer.approve'],
            ['label' => 'Approve warehouse stock transfers before dispatch', 'updated_at' => $now, 'created_at' => $now],
        );
        $approvalPermissionId = DB::table('permissions')->where('name', 'transfer.approve')->value('id');
        $approverRoleIds = DB::table('roles')->whereIn('name', ['super_admin', 'pic_warehouse'])->pluck('id');
        foreach ($approverRoleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(['permission_id' => $approvalPermissionId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->dropColumn('transfer_approval_enabled');
        });

        $permissionId = DB::table('permissions')->where('name', 'transfer.approve')->value('id');
        $roleId = DB::table('roles')->where('name', 'pic_warehouse')->value('id');
        if ($permissionId) DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        if ($roleId) {
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
        if ($permissionId) DB::table('permissions')->where('id', $permissionId)->delete();

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['prepared_by', 'approved_by', 'approved_at']);
            $table->timestamp('dispatched_at')->nullable(false)->change();
            $table->foreignId('dispatched_by')->nullable(false)->change();
        });
    }
};
