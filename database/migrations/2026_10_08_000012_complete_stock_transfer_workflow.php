<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('is_active')->constrained('locations')->nullOnDelete();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('received_quantity', 14, 3)->default(0)->after('quantity');
            $table->decimal('discrepancy_quantity', 14, 3)->default(0)->after('received_quantity');
            $table->text('discrepancy_reason')->nullable()->after('discrepancy_quantity');
        });

        Schema::create('stock_transfer_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->boolean('is_final')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['stock_transfer_id', 'received_at']);
        });

        Schema::create('stock_transfer_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_receipt_id')->constrained('stock_transfer_receipts')->cascadeOnDelete();
            $table->foreignId('stock_transfer_item_id')->constrained('stock_transfer_items')->restrictOnDelete();
            $table->foreignId('stock_in_item_id')->nullable()->constrained('stock_in_items')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->timestamps();
            $table->index(['stock_transfer_item_id', 'stock_transfer_receipt_id'], 'transfer_receipt_item_lookup_index');
        });

        // Preserve legacy full receipts in the new receipt ledger.
        DB::table('stock_transfers')->where('status', 'received')->orderBy('id')->each(function ($transfer) {
            $receiptId = DB::table('stock_transfer_receipts')->insertGetId([
                'stock_transfer_id' => $transfer->id,
                'stock_in_id' => $transfer->stock_in_id,
                'received_by' => $transfer->received_by,
                'received_at' => $transfer->received_at ?? $transfer->updated_at,
                'is_final' => true,
                'notes' => null,
                'created_at' => $transfer->created_at,
                'updated_at' => $transfer->updated_at,
            ]);
            foreach (DB::table('stock_transfer_items')->where('stock_transfer_id', $transfer->id)->get() as $item) {
                DB::table('stock_transfer_items')->where('id', $item->id)->update(['received_quantity' => $item->quantity]);
                DB::table('stock_transfer_receipt_items')->insert([
                    'stock_transfer_receipt_id' => $receiptId,
                    'stock_transfer_item_id' => $item->id,
                    'stock_in_item_id' => $item->stock_in_item_id,
                    'quantity' => $item->quantity,
                    'created_at' => $transfer->created_at,
                    'updated_at' => $transfer->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_receipt_items');
        Schema::dropIfExists('stock_transfer_receipts');
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'discrepancy_quantity', 'discrepancy_reason']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
