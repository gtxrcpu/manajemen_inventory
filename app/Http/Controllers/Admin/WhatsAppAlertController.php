<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\NotificationProvider;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAlertLog;
use App\Models\WhatsAppAlertSetting;
use App\Services\LowStockAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WhatsAppAlertController extends Controller
{
    public function status(NotificationProvider $provider): JsonResponse
    {
        return response()->json([
            'connection' => $provider->status(),
            'qr' => $provider->qr(),
        ])->header('Cache-Control', 'no-store');
    }

    public function index(NotificationProvider $provider): View
    {
        $settings = WhatsAppAlertSetting::query()->firstOrCreate(['id' => 1]);

        return view('admin.whatsapp-alerts.index', [
            'settings' => $settings,
            'connection' => $provider->status(),
            'qr' => $provider->qr(),
            'logs' => WhatsAppAlertLog::with('alert.product', 'alert.location')->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, NotificationProvider $provider, LowStockAlertService $lowStock): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['nullable', 'string', 'max:4000'],
            'message_template' => ['required', 'string', 'max:1000'],
            'enabled' => ['nullable', 'boolean'],
            'risk_accepted' => ['nullable', 'boolean'],
        ]);

        $recipients = collect(preg_split('/[,;\r\n]+/', (string) ($data['recipients'] ?? '')))
            ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone))
            ->filter()->unique()->values();
        foreach ($recipients as $phone) {
            if (! preg_match('/^[1-9][0-9]{7,14}$/', $phone)) {
                throw ValidationException::withMessages(['recipients' => 'Gunakan nomor internasional, misalnya 6281234567890.']);
            }
        }

        $riskAccepted = (bool) ($data['risk_accepted'] ?? false);
        $enabled = (bool) ($data['enabled'] ?? false);
        if ($enabled && ! config('services.whatsapp.provider_approved')) {
            throw ValidationException::withMessages(['enabled' => 'Provider WhatsApp belum disetujui untuk diaktifkan. Tinjau risiko provider dan hasil audit dependency terlebih dahulu.']);
        }
        if ($enabled && ! $riskAccepted) {
            throw ValidationException::withMessages(['risk_accepted' => 'Persetujuan risiko wajib dicentang sebelum notifikasi WhatsApp diaktifkan.']);
        }
        if ($enabled && $recipients->isEmpty()) {
            throw ValidationException::withMessages(['recipients' => 'Tambahkan setidaknya satu nomor penerima sebelum mengaktifkan notifikasi.']);
        }
        $settings = WhatsAppAlertSetting::query()->firstOrCreate(['id' => 1]);
        $settings->update([
            'enabled' => $enabled,
            'recipients' => $recipients->all(),
            'message_template' => $data['message_template'],
            'risk_accepted_at' => $riskAccepted ? ($settings->risk_accepted_at ?? now()) : null,
        ]);

        $obsoleteLogs = WhatsAppAlertLog::query()->whereIn('status', ['queued', 'sending']);
        if ($enabled && $recipients->isNotEmpty()) {
            $obsoleteLogs->whereNotIn('recipient', $recipients->all());
        }
        $obsoleteLogs->update([
            'status' => 'failed',
            'error_message' => $enabled
                ? 'Nomor penerima sudah tidak aktif di pengaturan WhatsApp.'
                : 'Pengiriman WhatsApp dinonaktifkan sebelum pesan dikirim.',
        ]);

        if ($enabled) {
            $lowStock->queueActiveWhatsAppAlerts();
            if (! ($provider->status()['connected'] ?? false)) {
                return back()->with('status', 'Nomor penerima dan pengaturan alert tersimpan. Alert aktif akan menunggu service WhatsApp siap sebelum dikirim.');
            }
        }

        return back()->with('status', $enabled
            ? 'Notifikasi WhatsApp aktif. Alert stok aktif dan alert baru akan dikirim melalui antrean.'
            : 'Pengaturan tersimpan. Pengiriman WhatsApp tetap nonaktif.');
    }
}
