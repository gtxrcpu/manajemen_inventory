<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->json('location_recipients')->nullable()->after('recipients');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->dropColumn('location_recipients');
        });
    }
};
