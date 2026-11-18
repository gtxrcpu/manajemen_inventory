<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ins', function (Blueprint $table) {
            $table->foreignId('purchase_invoice_id')->nullable()->after('purchase_order_id')->constrained()->restrictOnDelete();
        });

        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->decimal('invoice_quantity', 14, 3)->nullable()->after('delivered_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->dropColumn('invoice_quantity');
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_invoice_id');
        });
    }
};
