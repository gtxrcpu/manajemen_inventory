<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('stock_out_id')->nullable()->constrained('stock_outs')->nullOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->nullOnDelete();
            $table->foreignId('dispatched_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('in_transit')->index();
            $table->timestamp('dispatched_at');
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['source_location_id', 'destination_location_id', 'status'], 'stock_transfer_route_status_index');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('source_batch_id')->nullable()->constrained('stock_in_items')->nullOnDelete();
            $table->foreignId('destination_batch_id')->nullable()->constrained('stock_in_items')->nullOnDelete();
            $table->foreignId('stock_out_item_id')->nullable()->unique()->constrained('stock_out_items')->nullOnDelete();
            $table->foreignId('stock_in_item_id')->nullable()->unique()->constrained('stock_in_items')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->index(['stock_transfer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
