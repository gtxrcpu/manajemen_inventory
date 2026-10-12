<?php

namespace App\Services;

use App\Models\LowStockAlert;
use App\Models\ProductLocationStockThreshold;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;
use App\Models\WhatsAppAlertLog;
use App\Jobs\SendLowStockWhatsAppAlert;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;

class LowStockAlertService
{
    public function sync(int $productId, int $locationId): void
    {
        $newAlert = DB::transaction(function () use ($productId, $locationId) {
            $threshold = ProductLocationStockThreshold::query()
                ->where('product_id', $productId)->where('location_id', $locationId)->lockForUpdate()->first();
            if (! $threshold) return null;

            $stock = (float) StockMovement::query()->where('product_id', $productId)->where('location_id', $locationId)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as balance")
                ->value('balance');
            $activeAlert = LowStockAlert::active()->where('product_id', $productId)->where('location_id', $locationId)->lockForUpdate()->first();
            $minimum = (float) $threshold->minimum_quantity;

            if ($minimum > 0 && $stock <= $minimum + 0.0000005) {
                if ($activeAlert) {
                    $activeAlert->update(['minimum_quantity' => $minimum, 'stock_quantity' => $stock]);
                    return null;
                }
                return LowStockAlert::create([
                    'product_id' => $productId, 'location_id' => $locationId,
                    'minimum_quantity' => $minimum, 'stock_quantity' => $stock, 'triggered_at' => now(),
                ]);
            }

            if ($activeAlert) $activeAlert->update(['stock_quantity' => $stock, 'resolved_at' => now()]);
            return null;
        });

        if ($newAlert) $this->notifyRecipients($newAlert);
    }

    public function saveThresholds(int $locationId, array $thresholds): void
    {
        foreach ($thresholds as $productId => $minimum) {
            ProductLocationStockThreshold::updateOrCreate(
                ['product_id' => (int) $productId, 'location_id' => $locationId],
                ['minimum_quantity' => $minimum],
            );
            $this->sync((int) $productId, $locationId);
        }
    }

    public function activeForUser(User $user)
    {
        return LowStockAlert::active()->with(['product.unit', 'location'])
            ->when($user->hasRole('pic_outlet'), fn ($query) => $query->where('location_id', $user->location_id))
            ->latest('triggered_at');
    }

    public function queueActiveWhatsAppAlerts(): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $settings?->enabled || ! $settings->risk_accepted_at || empty($settings->recipients)) return 0;

        $queued = 0;
        foreach (LowStockAlert::active()->get() as $alert) {
            foreach ($settings->recipients as $phone) {
                $queued += (int) $this->queueWhatsAppAlert($alert, $phone);
            }
        }

        return $queued;
    }

    private function notifyRecipients(LowStockAlert $alert): void
    {
        $location = $alert->location;
        $recipients = User::query()->where('is_active', true)->where(function ($query) use ($location) {
            $query->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['super_admin', 'staff_gudang']));
            if ($location->type === 'outlet') {
                $query->orWhere(fn ($pic) => $pic->where('location_id', $location->id)
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_outlet')));
            }
        })->get();

        foreach ($recipients as $recipient) $recipient->notify(new LowStockNotification($alert));

        $settings = WhatsAppAlertSetting::query()->first();
        if ($settings?->enabled && $settings->risk_accepted_at) {
            foreach ($settings->recipients ?? [] as $phone) {
                $this->queueWhatsAppAlert($alert, $phone);
            }
        }
    }

    private function queueWhatsAppAlert(LowStockAlert $alert, string $phone): bool
    {
        $log = WhatsAppAlertLog::firstOrNew([
            'low_stock_alert_id' => $alert->id,
            'recipient' => $phone,
        ]);

        if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) return false;

        $log->status = 'queued';
        $log->error_message = null;
        $log->save();
        SendLowStockWhatsAppAlert::dispatch($alert->id, $phone);

        return true;
    }
}
