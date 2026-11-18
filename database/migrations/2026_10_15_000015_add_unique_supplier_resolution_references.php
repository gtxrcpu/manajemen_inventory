<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_disposal_supplier_resolutions', function (Blueprint $table) {
            $table->unique(['stock_disposal_item_id', 'resolution_type', 'reference'], 'sdsr_item_type_ref_uq');
            $table->unique(['stock_disposal_item_id', 'replacement_stock_in_id'], 'sdsr_item_receipt_uq');
        });
    }

    public function down(): void
    {
        Schema::table('stock_disposal_supplier_resolutions', function (Blueprint $table) {
            $table->dropUnique('sdsr_item_type_ref_uq');
            $table->dropUnique('sdsr_item_receipt_uq');
        });
    }
};
