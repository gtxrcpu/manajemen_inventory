<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('review_category', 40)->nullable()->after('review_note');
        });
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->string('rejection_category', 40)->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('stock_ins', fn (Blueprint $table) => $table->dropColumn('rejection_category'));
        Schema::table('purchase_invoices', fn (Blueprint $table) => $table->dropColumn('review_category'));
    }
};
