<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL uses the existing unique indexes to support these foreign keys,
        // so create equivalent non-unique indexes before dropping the uniques.
        Schema::table('stock_in_items', fn (Blueprint $table) => $table->index(['stock_in_id', 'product_id'], 'stock_in_items_receipt_product_index'));
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->dropUnique(['stock_in_id', 'product_id']);
            $table->string('batch_number', 100)->nullable()->after('quantity');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });

        Schema::table('stock_out_items', fn (Blueprint $table) => $table->index(['stock_out_id', 'product_id'], 'stock_out_items_issue_product_index'));
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->dropUnique(['stock_out_id', 'product_id']);
            $table->foreignId('batch_id')->nullable()->after('product_id')->constrained('stock_in_items')->restrictOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('user_id')->constrained('stock_in_items')->restrictOnDelete();
            $table->index(['batch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['batch_id', 'created_at']);
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->unique(['stock_out_id', 'product_id']);
        });
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropIndex('stock_out_items_issue_product_index');
        });
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->unique(['stock_in_id', 'product_id']);
        });
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'batch_number']);
            $table->dropIndex('stock_in_items_receipt_product_index');
        });
    }
};
