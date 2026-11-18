<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->timestamp('expected_arrival_at')->nullable()->after('dispatched_at');
        });

        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->boolean('transfer_arrival_enabled')->default(false)->after('transfer_approval_enabled');
        });

        Schema::create('stock_transfer_arrival_whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->restrictOnDelete();
            $table->string('recipient', 32);
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['stock_transfer_id', 'recipient'], 'transfer_arrival_whatsapp_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_arrival_whatsapp_logs');
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) { $table->dropColumn('transfer_arrival_enabled'); });
        Schema::table('stock_transfers', function (Blueprint $table) { $table->dropColumn('expected_arrival_at'); });
    }
};
