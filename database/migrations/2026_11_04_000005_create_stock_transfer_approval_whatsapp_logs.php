<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_approval_whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->restrictOnDelete();
            $table->string('recipient', 24);
            $table->string('status', 16)->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['stock_transfer_id', 'recipient'], 'stock_transfer_approval_whatsapp_recipient_unique');
            $table->index(['status', 'created_at'], 'stock_transfer_approval_whatsapp_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_approval_whatsapp_logs');
    }
};
