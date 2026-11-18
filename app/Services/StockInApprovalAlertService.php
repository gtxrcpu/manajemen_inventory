<?php

namespace App\Services;

use App\Jobs\SendStockInApprovalWhatsAppAlert;
use App\Models\Location;
use App\Models\StockIn;
use App\Models\StockInApprovalWhatsAppLog;
use App\Models\User;
use App\Models\WhatsAppAlertSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockInApprovalAlertService
{
    public function queue(StockIn $stockIn): int
    {
        $settings = WhatsAppAlertSetting::query()->first();
        if (! $this->enabled($settings) || $stockIn->status !== 'pending_approval') return 0;

        $queued = 0;
        foreach ($this->picRecipientsForLocation((int) $stockIn->location_id, $settings) as $phone) {
            $log = StockInApprovalWhatsAppLog::query()->firstOrNew(['stock_in_id' => $stockIn->id, 'recipient' => $phone]);
            if ($log->exists && in_array($log->status, ['queued', 'sending', 'sent'], true)) continue;
            $log->status = 'queued';
            $log->error_message = null;
            $log->save();
            SendStockInApprovalWhatsAppAlert::dispatch((int) $log->id);
            $queued++;
        }

        return $queued;
    }

    public function queuePendingApprovals(): int
    {
        if (! Schema::hasTable('stock_in_approval_whatsapp_logs')) return 0;
        $queued = 0;
        StockIn::query()->where('status', 'pending_approval')->orderBy('id')->chunkById(100, function ($receipts) use (&$queued) {
            foreach ($receipts as $receipt) $queued += $this->queue($receipt);
        });
        return $queued;
    }

    public function isRecipientForStockIn(StockIn $stockIn, string $phone): bool
    {
        $settings = WhatsAppAlertSetting::query()->first();
        return $this->enabled($settings)
            && $stockIn->status === 'pending_approval'
            && in_array($phone, $this->picRecipientsForLocation((int) $stockIn->location_id, $settings), true);
    }

    /** @return array<int, string> */
    public function picRecipientsForLocation(int $locationId, ?WhatsAppAlertSetting $settings = null): array
    {
        $location = Location::query()->whereKey($locationId)->where('type', Location::TYPE_OUTLET)->where('is_active', true)->first();
        if (! $location) return [];

        $primaryPicIds = User::query()->where('is_active', true)->where('location_id', $locationId)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_outlet'))->pluck('id')->all();
        $substituteIds = [];
        if (Schema::hasTable('pic_substitute_assignments')) {
            $substituteIds = DB::table('pic_substitute_assignments')->where('location_id', $locationId)
                ->whereNull('revoked_at')->where('starts_at', '<=', now())->where('ends_at', '>', now())
                ->pluck('substitute_pic_user_id')->all();
        }
        $userPhones = User::query()->where('is_active', true)->whereNotNull('whatsapp_phone')
            ->whereIn('id', [...$primaryPicIds, ...$substituteIds])
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_outlet'))
            ->get()->filter(fn (User $user) => $user->canAccessOutletLocation($locationId))
            ->pluck('whatsapp_phone')->all();
        $manualPhones = $settings?->location_recipients[(string) $locationId] ?? [];

        return collect([...$userPhones, ...$manualPhones])
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => preg_match('/^[1-9][0-9]{7,14}$/', $phone))
            ->unique()->values()->all();
    }

    private function enabled(?WhatsAppAlertSetting $settings): bool
    {
        return config('services.whatsapp.provider_approved')
            && $settings?->stock_in_approval_enabled
            && $settings->risk_accepted_at;
    }
}
