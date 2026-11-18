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
            $table->json('warehouse_staff_recipients')->nullable()->after('location_recipients');
            $table->json('warehouse_pic_recipients')->nullable()->after('warehouse_staff_recipients');
            $table->boolean('stock_in_approval_enabled')->default(false)->after('transfer_approval_enabled');
        });

        $warehouseIds = DB::table('locations')->where('type', 'warehouse')->pluck('id')->map(fn ($id) => (string) $id)->all();
        foreach (DB::table('whatsapp_alert_settings')->get() as $settings) {
            $legacy = is_string($settings->location_recipients)
                ? json_decode($settings->location_recipients, true)
                : (array) $settings->location_recipients;
            $staff = [];
            $pic = [];
            foreach ($warehouseIds as $locationId) {
                $phones = $legacy[$locationId] ?? [];
                if (! is_array($phones) || $phones === []) continue;
                $staff[$locationId] = array_values($phones);
                $pic[$locationId] = array_values($phones);
            }
            DB::table('whatsapp_alert_settings')->where('id', $settings->id)->update([
                'warehouse_staff_recipients' => json_encode($staff),
                'warehouse_pic_recipients' => json_encode($pic),
            ]);
        }

        Schema::create('stock_in_approval_whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained('stock_ins')->restrictOnDelete();
            $table->string('recipient', 24);
            $table->string('status', 16)->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['stock_in_id', 'recipient'], 'stock_in_approval_whatsapp_recipient_unique');
            $table->index(['status', 'created_at'], 'stock_in_approval_whatsapp_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_approval_whatsapp_logs');
        Schema::table('whatsapp_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['warehouse_staff_recipients', 'warehouse_pic_recipients', 'stock_in_approval_enabled']);
        });
    }
};
