<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_id']);
            $table->unsignedBigInteger('purchase_request_id')->nullable()->change();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->restrictOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('po_number', 100)->nullable()->unique()->after('reference');
            $table->foreignId('destination_location_id')->nullable()->after('location_id')->constrained('locations')->restrictOnDelete();
            $table->date('order_date')->nullable()->after('destination_location_id');
            $table->date('expected_delivery_date')->nullable()->after('order_date');
            $table->string('payment_terms', 40)->default('net_14')->after('expected_delivery_date');
            $table->string('delivery_terms', 255)->nullable()->after('payment_terms');
            $table->decimal('subtotal', 16, 2)->default(0)->after('delivery_terms');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('subtotal');
            $table->decimal('tax_amount', 16, 2)->default(0)->after('tax_rate');
            $table->decimal('shipping_cost', 16, 2)->default(0)->after('tax_amount');
            $table->decimal('other_cost', 16, 2)->default(0)->after('shipping_cost');
            $table->decimal('grand_total', 16, 2)->default(0)->after('other_cost');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
        DB::statement("ALTER TABLE purchase_orders MODIFY status VARCHAR(32) NOT NULL DEFAULT 'draft'");

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('description', 1000)->nullable()->after('product_id');
            $table->decimal('qty_ordered', 14, 3)->default(0)->after('quantity');
            $table->decimal('qty_received', 14, 3)->default(0)->after('qty_ordered');
            $table->decimal('discount_amount', 14, 2)->default(0)->after('unit_price');
            $table->decimal('subtotal', 16, 2)->default(0)->after('discount_amount');
        });

        DB::table('purchase_orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                DB::table('purchase_orders')->where('id', $order->id)->update([
                    'po_number' => $order->reference,
                    'destination_location_id' => $order->location_id,
                    'order_date' => substr((string) $order->created_at, 0, 10),
                    'expected_delivery_date' => $order->expected_delivery,
                    'status' => match ($order->status) { 'issued' => 'open', 'received' => 'completed', default => $order->status },
                ]);
            }
        });

        DB::table('purchase_order_items')->orderBy('id')->chunkById(200, function ($items) {
            foreach ($items as $item) {
                $qty = (float) $item->quantity;
                $price = (float) ($item->unit_price ?? 0);
                DB::table('purchase_order_items')->where('id', $item->id)->update([
                    'qty_ordered' => $qty,
                    'subtotal' => round($qty * $price, 2),
                ]);
            }
        });

        $received = DB::table('stock_in_items as items')
            ->join('stock_ins as receipts', 'receipts.id', '=', 'items.stock_in_id')
            ->whereIn('receipts.status', ['approved', 'pending_approval'])->whereNotNull('items.purchase_order_item_id')
            ->select('items.purchase_order_item_id')->selectRaw('SUM(items.quantity) as qty_received')
            ->groupBy('items.purchase_order_item_id')->get();
        foreach ($received as $line) {
            DB::table('purchase_order_items')->where('id', $line->purchase_order_item_id)->update(['qty_received' => $line->qty_received]);
        }

        DB::table('purchase_orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                if ($order->status === 'open') {
                    $items = DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->get(['qty_ordered', 'qty_received']);
                    $receivedAny = $items->contains(fn ($item) => (float) $item->qty_received > 0);
                    $complete = $items->isNotEmpty() && $items->every(fn ($item) => (float) $item->qty_received + 0.0005 >= (float) $item->qty_ordered);
                    $status = $complete ? 'completed' : ($receivedAny ? 'partially_received' : 'open');
                    if ($status !== 'open') DB::table('purchase_orders')->where('id', $order->id)->update(['status' => $status]);
                }
            }
        });

        DB::table('purchase_orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $subtotal = (float) DB::table('purchase_order_items')->where('purchase_order_id', $order->id)->sum('subtotal');
                DB::table('purchase_orders')->where('id', $order->id)->update([
                    'subtotal' => $subtotal,
                    'grand_total' => $subtotal,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('purchase_orders')->whereIn('status', ['open', 'partially_received', 'draft', 'cancelled'])->update(['status' => 'issued']);
        DB::table('purchase_orders')->whereIn('status', ['completed'])->update(['status' => 'received']);
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'qty_ordered', 'qty_received', 'discount_amount', 'subtotal']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_location_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropUnique(['po_number']);
            $table->dropColumn(['po_number', 'order_date', 'expected_delivery_date', 'payment_terms', 'delivery_terms', 'subtotal', 'tax_rate', 'tax_amount', 'shipping_cost', 'other_cost', 'grand_total', 'approved_at']);
        });
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('issued', 'received') NOT NULL DEFAULT 'issued'");
    }
};
