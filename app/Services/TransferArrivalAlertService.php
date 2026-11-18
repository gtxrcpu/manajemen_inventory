<?php

namespace App\Services;

use App\Jobs\SendTransferArrivalWhatsAppAlert;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\StockTransferArrivalWhatsAppLog;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;

class TransferArrivalAlertService
{
    public function queue(StockTransfer $transfer): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $this->enabled($settings) || $transfer->status !== 'in_transit' || ! $transfer->expected_arrival_at) return 0;

        $queued = 0;
        foreach ($this->staffRecipientsForOutlet((int) $transfer->destination_location_id) as $phone) {
            $log = StockTransferArrivalWhatsAppLog::query()->firstOrNew([
                'stock_transfer_id' => $transfer->id,
                'recipient' => $phone,
            ]);
            if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) continue;

            $log->status = 'queued';
            $log->error_message = null;
            $log->save();
            SendTransferArrivalWhatsAppAlert::dispatch((int) $log->id);
            $queued++;
        }

        return $queued;
    }

    public function isRecipientForTransfer(StockTransfer $transfer, string $phone): bool
    {
        $settings = WhatsAppAlertSetting::query()->first();
        return $this->enabled($settings)
            && $transfer->status === 'in_transit'
            && $transfer->expected_arrival_at !== null
            && in_array($phone, $this->staffRecipientsForOutlet((int) $transfer->destination_location_id), true);
    }

    /** @return array<int, string> */
    public function staffRecipientsForOutlet(int $locationId): array
    {
        $location = Location::query()->whereKey($locationId)->where('type', Location::TYPE_OUTLET)->where('is_active', true)->first();
        if (! $location) return [];

        return User::query()->where('is_active', true)->where('location_id', $locationId)
            ->whereNotNull('whatsapp_phone')
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_outlet'))
            ->pluck('whatsapp_phone')
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))
            ->unique()->values()->all();
    }

    private function enabled(?WhatsAppAlertSetting $settings): bool
    {
        return config('services.whatsapp.provider_approved')
            && $settings?->transfer_arrival_enabled
            && $settings->risk_accepted_at;
    }
}
