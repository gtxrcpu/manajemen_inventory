<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->string('status', 32)->default('approved')->after('notes')->index();
            $table->foreignId('decision_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decision_by');
            $table->text('rejection_reason')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decision_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'decided_at', 'rejection_reason']);
        });
    }
};
