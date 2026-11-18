<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_payments', function (Blueprint $table) {
            $table->string('entry_type', 16)->default('payment')->after('purchase_invoice_id');
            $table->foreignId('reversal_of_id')->nullable()->after('entry_type')->constrained('purchase_invoice_payments')->restrictOnDelete();
            $table->text('reversal_reason')->nullable()->after('notes');
            $table->unique('reversal_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_payments', function (Blueprint $table) {
            $table->dropUnique(['reversal_of_id']);
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropColumn(['entry_type', 'reversal_reason']);
        });
    }
};
