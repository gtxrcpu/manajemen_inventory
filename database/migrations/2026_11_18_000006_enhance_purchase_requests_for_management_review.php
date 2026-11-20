<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Nullable for existing historical PRs; all new submissions validate these fields.
            $table->foreignId('location_id')->nullable()->after('requested_by')->constrained('locations')->restrictOnDelete();
            $table->string('priority', 16)->default('normal')->after('location_id');
            $table->text('justification')->nullable()->after('priority');
            $table->foreignId('recommended_supplier_id')->nullable()->after('justification')->constrained('suppliers')->nullOnDelete();
            $table->decimal('estimated_total', 16, 2)->nullable()->after('recommended_supplier_id');
            $table->foreignId('approved_by')->nullable()->after('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users')->restrictOnDelete();
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->decimal('stock_snapshot', 14, 3)->nullable()->after('quantity');
            $table->decimal('estimated_unit_price', 14, 2)->nullable()->after('stock_snapshot');
            $table->decimal('estimated_subtotal', 16, 2)->nullable()->after('estimated_unit_price');
            $table->string('notes', 1000)->nullable()->after('estimated_subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn(['stock_snapshot', 'estimated_unit_price', 'estimated_subtotal', 'notes']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['priority', 'justification', 'estimated_total']);
            $table->dropConstrainedForeignId('recommended_supplier_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
        });
    }
};
