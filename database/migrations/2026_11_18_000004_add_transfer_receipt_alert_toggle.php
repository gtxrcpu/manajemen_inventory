<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->boolean('transfer_receipt_enabled')->default(false)->after('transfer_arrival_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->dropColumn('transfer_receipt_enabled');
        });
    }
};
