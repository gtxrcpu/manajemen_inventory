<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100)->unique();
            $table->enum('period_type', ['weekly', 'month_end']);
            $table->enum('status', ['counting', 'pending_approval', 'approved', 'rejected'])->default('counting');
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('counted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('snapshot_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('movement_cutoff_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'period_type']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 14, 3);
            $table->decimal('physical_quantity', 14, 3)->nullable();
            $table->decimal('variance', 14, 3)->default(0);
            $table->decimal('adjustment_quantity', 14, 3)->default(0);
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'product_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_opname_item_id')->nullable()->after('stock_out_item_id')->constrained('stock_opname_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('stock_opname_item_id'));
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
