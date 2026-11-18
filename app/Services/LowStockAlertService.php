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
use Illuminate\Support\Facades\Schema;

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
        $locationIds = $user->isLocationScopedUser() ? $user->assignedLocationIds() : [];
        return LowStockAlert::active()->with(['product.unit', 'location'])
            ->when($user->isLocationScopedUser(), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->latest('triggered_at');
    }

    public function queueActiveWhatsAppAlerts(): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $settings?->low_stock_enabled || ! $settings->risk_accepted_at) return 0;

        $queued = 0;
        foreach (LowStockAlert::active()->get() as $alert) {
            foreach ($this->whatsAppRecipients($alert) as $phone) {
                $queued += (int) $this->queueWhatsAppAlert($alert, $phone);
            }
        }

        return $queued;
    }

    private function notifyRecipients(LowStockAlert $alert): void
    {
        $location = $alert->location;
        $recipients = User::query()->where('is_active', true)->where(function ($query) use ($location) {
            $query->whereHas('roles', fn ($roles) => $roles->where('name', 'super_admin'));
            if ($location->type === 'outlet') {
                $query->orWhere(fn ($outletUser) => $outletUser->where(function ($scope) use ($location) {
                    $scope->where('location_id', $location->id);
                    $scope->orWhereIn('id', $this->activeSubstituteIds((int) $location->id));
                })->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['pic_outlet', 'staff_outlet'])));
            } elseif ($location->type === 'warehouse') {
                $query->orWhere(fn ($warehouseStaff) => $warehouseStaff->where('location_id', $location->id)
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_gudang')));
            }
        })->get();

        foreach ($recipients as $recipient) $recipient->notify(new LowStockNotification($alert));

        $settings = WhatsAppAlertSetting::query()->first();
        if ($settings?->low_stock_enabled && $settings->risk_accepted_at) {
            foreach ($this->whatsAppRecipients($alert) as $phone) {
                $this->queueWhatsAppAlert($alert, $phone);
            }
        }
    }

    public function isWhatsAppRecipientForAlert(LowStockAlert $alert, string $phone): bool
    {
        $mappedRecipients = WhatsAppAlertSetting::query()->first()?->location_recipients ?? [];
        $warehouseStaffRecipients = WhatsAppAlertSetting::query()->first()?->warehouse_staff_recipients ?? [];
        if (in_array($phone, $mappedRecipients[(string) $alert->location_id] ?? [], true)
            || in_array($phone, $warehouseStaffRecipients[(string) $alert->location_id] ?? [], true)) return true;
        if ($alert->location?->type === 'warehouse') {
            return User::query()->where('is_active', true)->where('location_id', $alert->location_id)
                ->where('whatsapp_phone', $phone)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_gudang'))->exists();
        }
        if ($alert->location?->type !== 'outlet') return false;

        return User::query()->where('is_active', true)->where(function ($query) use ($alert) {
                $query->where('location_id', $alert->location_id);
                $query->orWhereIn('id', $this->activeSubstituteIds((int) $alert->location_id));
            })
            ->where('whatsapp_phone', $phone)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['pic_outlet', 'staff_outlet']))
            ->exists();
    }

    /** @return array<int, array{name: string, whatsapp_phone: string}> */
    public function outletWhatsAppRecipientsForLocation(int $locationId): array
    {
        $substituteIds = $this->activeSubstituteIds($locationId);

        return User::query()->where('is_active', true)->whereNotNull('whatsapp_phone')
            ->where(function ($query) use ($locationId, $substituteIds) {
                $query->where('location_id', $locationId)->orWhereIn('id', $substituteIds);
            })
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['pic_outlet', 'staff_outlet']))
            ->get(['id', 'name', 'whatsapp_phone'])->map(fn (User $user) => [
                'name' => $user->name,
                'role_label' => $user->hasRole('pic_outlet') ? 'PIC Outlet' : 'Staff Outlet',
                'whatsapp_phone' => $user->whatsapp_phone,
            ])->all();
    }

    private function whatsAppRecipients(LowStockAlert $alert): array
    {
        $settings = WhatsAppAlertSetting::query()->first();
        $recipients = $settings?->location_recipients[(string) $alert->location_id] ?? [];
        if ($alert->location?->type === 'outlet') {
            $picPhones = collect($this->outletWhatsAppRecipientsForLocation((int) $alert->location_id))
                ->pluck('whatsapp_phone')->all();
            $recipients = array_merge($recipients, $picPhones);
        } elseif ($alert->location?->type === 'warehouse') {
            $staffManual = $settings?->warehouse_staff_recipients[(string) $alert->location_id] ?? [];
            $recipients = array_merge($recipients, $staffManual);
            $warehousePhones = User::query()->where('is_active', true)->whereNotNull('whatsapp_phone')
                ->where('location_id', $alert->location_id)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'staff_gudang'))
                ->pluck('whatsapp_phone')->all();
            $recipients = array_merge($recipients, $warehousePhones);
        }

        return array_values(array_unique(array_filter($recipients)));
    }

    private function activeSubstituteIds(int $locationId): array
    {
        if (! Schema::hasTable('pic_substitute_assignments')) return [];

        $candidateIds = DB::table('pic_substitute_assignments')->where('location_id', $locationId)
            ->whereNull('revoked_at')->where('starts_at', '<=', now())->where('ends_at', '>', now())
            ->pluck('substitute_pic_user_id');

        return User::query()->whereIn('id', $candidateIds)->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_outlet'))
            ->get()->filter(fn (User $user) => $user->canAccessOutletLocation($locationId))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
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
