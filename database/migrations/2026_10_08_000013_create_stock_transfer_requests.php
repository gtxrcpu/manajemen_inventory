<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stock_transfer_id')->nullable()->unique()->constrained('stock_transfers')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('requested_at');
            $table->timestamp('picked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_request_id')->constrained('stock_transfer_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->index(['stock_transfer_request_id', 'product_id'], 'transfer_request_product_index');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('stock_transfer_request_id')->nullable()->after('stock_in_id')->constrained('stock_transfer_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_transfer_request_id');
        });
        Schema::dropIfExists('stock_transfer_request_items');
        Schema::dropIfExists('stock_transfer_requests');
    }
};
