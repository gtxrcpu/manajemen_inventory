<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pic_substitute_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('primary_pic_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('substitute_pic_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('revoked_at')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->index(['substitute_pic_user_id', 'starts_at', 'ends_at'], 'pic_substitute_user_period_index');
            $table->index(['location_id', 'starts_at', 'ends_at'], 'pic_substitute_location_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pic_substitute_assignments');
    }
};
