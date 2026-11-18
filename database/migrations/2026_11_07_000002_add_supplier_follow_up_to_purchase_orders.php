<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier_follow_up_action', 32)->nullable()->after('status');
            $table->date('supplier_follow_up_due_at')->nullable()->after('supplier_follow_up_action');
            $table->text('supplier_follow_up_note')->nullable()->after('supplier_follow_up_due_at');
            $table->foreignId('supplier_follow_up_by')->nullable()->after('supplier_follow_up_note')->constrained('users')->nullOnDelete();
            $table->timestamp('supplier_follow_up_at')->nullable()->after('supplier_follow_up_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_follow_up_by']);
            $table->dropColumn(['supplier_follow_up_action', 'supplier_follow_up_due_at', 'supplier_follow_up_note', 'supplier_follow_up_by', 'supplier_follow_up_at']);
        });
    }
};
