<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->decimal('system_quantity_at_count', 14, 3)->nullable()->after('system_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', fn (Blueprint $table) => $table->dropColumn('system_quantity_at_count'));
    }
};
