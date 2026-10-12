<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->json('recipients')->nullable();
            $table->text('message_template')->nullable();
            $table->timestamp('risk_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('low_stock_alert_id')->constrained()->restrictOnDelete();
            $table->string('recipient', 24);
            $table->string('status', 16)->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['low_stock_alert_id', 'recipient'], 'whatsapp_alert_recipient_unique');
            $table->index(['status', 'created_at'], 'whatsapp_alert_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_alert_logs');
        Schema::dropIfExists('whatsapp_alert_settings');
    }
};
