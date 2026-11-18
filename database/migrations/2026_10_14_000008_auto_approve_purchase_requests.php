<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchase_requests')
            ->where('status', 'pending_approval')
            ->update(['status' => 'approved', 'reviewed_at' => now(), 'updated_at' => now()]);

        $permissionId = DB::table('permissions')->where('name', 'purchasing.approve')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }

    public function down(): void
    {
        // Automatic approvals are kept when rolling back to avoid reverting requests that may already have a PO.
    }
};
