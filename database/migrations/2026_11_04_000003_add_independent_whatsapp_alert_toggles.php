<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->boolean('low_stock_enabled')->default(false)->after('enabled');
            $table->boolean('supplier_arrival_enabled')->default(false)->after('low_stock_enabled');
        });

        DB::table('whatsapp_alert_settings')->where('enabled', true)->update([
            'low_stock_enabled' => true,
            'supplier_arrival_enabled' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['low_stock_enabled', 'supplier_arrival_enabled']);
        });
    }
};
