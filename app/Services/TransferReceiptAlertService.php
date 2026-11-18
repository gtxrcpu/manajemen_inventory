<?php

namespace App\Services;

use App\Jobs\SendTransferReceiptWhatsAppAlert;
use App\Models\Location;
use App\Models\StockTransferReceipt;
use App\Models\StockTransferReceiptWhatsAppLog;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;

class TransferReceiptAlertService
{
    public function queue(StockTransferReceipt $receipt): int
    {
        $receipt->loadMissing('transfer');
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $this->enabled($settings)) return 0;

        $queued = 0;
        foreach ($this->staffRecipientsForWarehouse((int) $receipt->transfer->source_location_id) as $phone) {
            $log = StockTransferReceiptWhatsAppLog::query()->firstOrNew([
                'stock_transfer_receipt_id' => $receipt->id,
                'recipient' => $phone,
            ]);
            if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) continue;

            $log->status = 'queued';
            $log->error_message = null;
            $log->save();
            SendTransferReceiptWhatsAppAlert::dispatch((int) $log->id);
            $queued++;
        }

        return $queued;
    }

    public function isRecipientForReceipt(StockTransferReceipt $receipt, string $phone): bool
    {
        $receipt->loadMissing('transfer');
        return $this->enabled(WhatsAppAlertSetting::query()->first())
            && in_array($phone, $this->staffRecipientsForWarehouse((int) $receipt->transfer->source_location_id), true);
    }

    /** @return array<int, string> */
    public function staffRecipientsForWarehouse(int $locationId): array
    {
        $warehouse = Location::query()->whereKey($locationId)->where('type', Location::TYPE_WAREHOUSE)->where('is_active', true)->first();
        if (! $warehouse) return [];

        $settings = WhatsAppAlertSetting::query()->first();
        $manualPhones = $settings?->warehouse_staff_recipients[(string) $locationId] ?? [];
        $staffPhones = User::query()->where('is_active', true)->where('location_id', $locationId)->whereNotNull('whatsapp_phone')
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_gudang'))
            ->pluck('whatsapp_phone')->all();

        return collect([...$staffPhones, ...$manualPhones])
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))
            ->unique()->values()->all();
    }

    private function enabled(?WhatsAppAlertSetting $settings): bool
    {
        return config('services.whatsapp.provider_approved')
            && $settings?->transfer_receipt_enabled
            && $settings->risk_accepted_at;
    }
}
