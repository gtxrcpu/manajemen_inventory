<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->foreignId('purchase_order_item_id')->nullable()->after('stock_in_id')->constrained()->restrictOnDelete();
            $table->decimal('delivered_quantity', 14, 3)->nullable()->after('quantity');
            $table->text('variance_note')->nullable()->after('delivered_quantity');
        });

        DB::table('stock_in_items as stock_items')
            ->join('stock_ins', 'stock_ins.id', '=', 'stock_items.stock_in_id')
            ->join('purchase_order_items', function ($join) {
                $join->on('purchase_order_items.purchase_order_id', '=', 'stock_ins.purchase_order_id')
                    ->on('purchase_order_items.product_id', '=', 'stock_items.product_id');
            })
            ->whereNotNull('stock_ins.purchase_order_id')
            ->select('stock_items.id', 'purchase_order_items.id as purchase_order_item_id', 'stock_items.quantity')
            ->orderBy('stock_items.id')
            ->chunk(500, function ($items) {
                foreach ($items as $item) {
                    DB::table('stock_in_items')->where('id', $item->id)->update([
                        'purchase_order_item_id' => $item->purchase_order_item_id,
                        'delivered_quantity' => $item->quantity,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_item_id');
            $table->dropColumn(['delivered_quantity', 'variance_note']);
        });
    }
};
