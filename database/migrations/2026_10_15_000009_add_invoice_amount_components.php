<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('discount_total', 16, 2)->default(0)->after('document_total');
            $table->decimal('tax_total', 16, 2)->default(0)->after('discount_total');
            $table->decimal('shipping_total', 16, 2)->default(0)->after('tax_total');
            $table->decimal('other_fee_total', 16, 2)->default(0)->after('shipping_total');
            $table->text('total_variance_note')->nullable()->after('other_fee_total');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'tax_total', 'shipping_total', 'other_fee_total', 'total_variance_note']);
        });
    }
};
