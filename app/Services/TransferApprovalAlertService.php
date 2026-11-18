<?php

namespace App\Services;

use App\Jobs\SendTransferApprovalWhatsAppAlert;
use App\Models\StockTransfer;
use App\Models\StockTransferApprovalWhatsAppLog;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;

class TransferApprovalAlertService
{
    public function queue(StockTransfer $transfer): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $this->enabled($settings) || $transfer->status !== 'pending_approval') return 0;

        $queued = 0;
        foreach ($this->recipientsForWarehouse((int) $transfer->source_location_id, $settings) as $phone) {
            $log = StockTransferApprovalWhatsAppLog::query()->firstOrNew([
                'stock_transfer_id' => $transfer->id,
                'recipient' => $phone,
            ]);
            if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) continue;

            $log->status = 'queued';
            $log->error_message = null;
            $log->save();
            SendTransferApprovalWhatsAppAlert::dispatch((int) $log->id);
            $queued++;
        }

        return $queued;
    }

    public function queuePendingApprovals(): int
    {
        $queued = 0;
        StockTransfer::query()->where('status', 'pending_approval')->orderBy('id')->chunkById(100, function ($transfers) use (&$queued) {
            foreach ($transfers as $transfer) $queued += $this->queue($transfer);
        });

        return $queued;
    }

    public function isRecipientForTransfer(StockTransfer $transfer, string $phone): bool
    {
        $settings = WhatsAppAlertSetting::query()->first();
        return $this->enabled($settings)
            && in_array($phone, $this->recipientsForWarehouse((int) $transfer->source_location_id, $settings), true);
    }

    /** @return array<int, array{name: string, whatsapp_phone: string}> */
    public function picWarehouseRecipientsForLocation(int $locationId): array
    {
        return User::query()->where('is_active', true)->whereNotNull('whatsapp_phone')
            ->where('location_id', $locationId)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_warehouse'))
            ->get(['name', 'whatsapp_phone'])->map(fn (User $user) => [
                'name' => $user->name,
                'whatsapp_phone' => $user->whatsapp_phone,
            ])->all();
    }

    /** @return array<int, string> */
    public function manualPicRecipientsForLocation(int $locationId, ?WhatsAppAlertSetting $settings = null): array
    {
        $settings ??= WhatsAppAlertSetting::query()->first();
        $mapped = $settings?->warehouse_pic_recipients;
        $configured = is_array($mapped) && array_key_exists((string) $locationId, $mapped)
            ? $mapped[(string) $locationId]
            : ($settings?->location_recipients[(string) $locationId] ?? []);

        return collect($configured)->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))->unique()->values()->all();
    }

    private function recipientsForWarehouse(int $locationId, ?WhatsAppAlertSetting $settings): array
    {
        $manualPhones = $this->manualPicRecipientsForLocation($locationId, $settings);
        $picPhones = collect($this->picWarehouseRecipientsForLocation($locationId))->pluck('whatsapp_phone')->all();

        return collect([...$manualPhones, ...$picPhones])
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))
            ->unique()->values()->all();
    }

    private function enabled(?WhatsAppAlertSetting $settings): bool
    {
        return config('services.whatsapp.provider_approved')
            && $settings?->transfer_approval_enabled
            && $settings->risk_accepted_at;
    }
}
