<?php

namespace App\Services;

use App\Jobs\SendPurchaseOrderArrivalWhatsAppAlert;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderWhatsAppLog;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;

class PurchaseOrderArrivalAlertService
{
    public function queue(PurchaseOrder $order): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $this->isWhatsAppEnabled($settings)) return 0;

        $order->loadMissing('location');
        $queued = 0;
        foreach ($this->recipientsForLocation((int) $order->location_id, $settings) as $recipient) {
            $log = PurchaseOrderWhatsAppLog::query()->firstOrNew([
                'purchase_order_id' => $order->id,
                'recipient' => $recipient,
            ]);
            if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) continue;

            $log->status = 'queued';
            $log->error_message = null;
            $log->save();
            SendPurchaseOrderArrivalWhatsAppAlert::dispatch((int) $log->id);
            $queued++;
        }

        return $queued;
    }

    public function queueUpcoming(): int
    {
        $queued = 0;
        PurchaseOrder::query()->whereIn('status', ['open', 'partially_received', 'issued'])->orderBy('id')->chunkById(100, function ($orders) use (&$queued) {
                foreach ($orders as $order) $queued += $this->queue($order);
            });

        return $queued;
    }

    public function isRecipientForOrder(PurchaseOrder $order, string $phone): bool
    {
        $settings = WhatsAppAlertSetting::query()->first();
        return $this->isWhatsAppEnabled($settings)
            && in_array($phone, $this->recipientsForLocation((int) $order->location_id, $settings), true);
    }

    /** @return array<int, array{name: string, whatsapp_phone: string}> */
    public function staffWhatsAppRecipientsForLocation(int $locationId): array
    {
        return User::query()->where('is_active', true)->whereNotNull('whatsapp_phone')
            ->where(fn ($query) => $query->whereNull('location_id')->orWhere('location_id', $locationId))
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_gudang'))
            ->get(['name', 'whatsapp_phone'])->map(fn (User $user) => [
                'name' => $user->name,
                'whatsapp_phone' => $user->whatsapp_phone,
            ])->all();
    }

    /** @return array<int, string> */
    public function manualStaffRecipientsForLocation(int $locationId, ?WhatsAppAlertSetting $settings = null): array
    {
        $settings ??= WhatsAppAlertSetting::query()->first();
        $mapped = $settings?->warehouse_staff_recipients;
        $configured = is_array($mapped) && array_key_exists((string) $locationId, $mapped)
            ? $mapped[(string) $locationId]
            : ($settings?->location_recipients[(string) $locationId] ?? []);

        return collect($configured)->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))->unique()->values()->all();
    }

    private function recipientsForLocation(int $locationId, ?WhatsAppAlertSetting $settings): array
    {
        $configured = $this->manualStaffRecipientsForLocation($locationId, $settings);
        $staffPhones = collect($this->staffWhatsAppRecipientsForLocation($locationId))
            ->pluck('whatsapp_phone')->all();

        return collect([...$configured, ...$staffPhones])
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))
            ->unique()->values()->all();
    }

    private function isWhatsAppEnabled(?WhatsAppAlertSetting $settings): bool
    {
        return config('services.whatsapp.provider_approved')
            && $settings?->supplier_arrival_enabled
            && $settings->risk_accepted_at;
    }
}
