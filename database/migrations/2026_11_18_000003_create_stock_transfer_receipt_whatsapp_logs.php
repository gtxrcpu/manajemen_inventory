<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_receipt_whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_receipt_id')->constrained('stock_transfer_receipts', 'id', 'transfer_receipt_alert_fk')->restrictOnDelete();
            $table->string('recipient', 32);
            $table->string('status', 20)->default('queued')->index();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['stock_transfer_receipt_id', 'recipient'], 'transfer_receipt_whatsapp_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_receipt_whatsapp_logs');
    }
};
