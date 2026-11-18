<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->string('type', 32);
            $table->string('status', 32)->default('pending_approval');
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('reason_category', 40);
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'status', 'created_at']);
        });

        Schema::create('stock_disposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_disposal_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('stock_in_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->index(['product_id', 'batch_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_disposal_item_id')->nullable()->after('stock_adjustment_id')->unique()
                ->constrained('stock_disposal_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('stock_disposal_item_id'));
        Schema::dropIfExists('stock_disposal_items');
        Schema::dropIfExists('stock_disposals');
    }
};
