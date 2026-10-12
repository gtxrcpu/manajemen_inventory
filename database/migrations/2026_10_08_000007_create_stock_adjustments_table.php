<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('direction', ['increase', 'decrease']);
            $table->decimal('quantity', 14, 3);
            $table->decimal('old_quantity', 14, 3);
            $table->decimal('new_quantity', 14, 3);
            $table->string('reason', 500);
            $table->timestamps();
            $table->index(['location_id', 'product_id', 'created_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_adjustment_id')->nullable()->after('stock_opname_item_id')->constrained('stock_adjustments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('stock_adjustment_id'));
        Schema::dropIfExists('stock_adjustments');
    }
};
