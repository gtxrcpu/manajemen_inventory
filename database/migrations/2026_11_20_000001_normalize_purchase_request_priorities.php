<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchase_requests')
            ->where('priority', 'urgent')
            ->update(['priority' => 'emergency']);
    }

    public function down(): void
    {
        // This data normalization is intentionally irreversible: emergency PRs
        // created after the migration must not be downgraded to the old "urgent" value.
    }
};
