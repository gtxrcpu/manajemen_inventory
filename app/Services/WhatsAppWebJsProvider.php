<?php

namespace App\Services;

use App\Contracts\NotificationProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppWebJsProvider implements NotificationProvider
{
    private function request(int $timeout = 2)
    {
        $url = rtrim((string) config('services.whatsapp.url'), '/');
        $token = (string) config('services.whatsapp.token');
        if ($url === '' || $token === '') {
            throw new RuntimeException('WhatsApp service belum dikonfigurasi.');
        }

        return Http::baseUrl($url)->withToken($token)->acceptJson()->connectTimeout(1)->timeout($timeout);
    }

    public function send(string $recipient, string $message): string
    {
        $response = $this->request(45)->post('/messages', ['phone' => $recipient, 'message' => $message])->throw()->json();
        return (string) ($response['message_id'] ?? 'accepted');
    }

    public function status(): array
    {
        try {
            return $this->request()->get('/status')->throw()->json() ?? ['connected' => false];
        } catch (\Throwable $exception) {
            report($exception);
            return ['connected' => false, 'error' => 'Layanan WhatsApp belum berjalan. Jalankan whatsapp-service pada komputer server aplikasi.'];
        }
    }

    public function qr(): ?string
    {
        try {
            return $this->request()->get('/qr')->throw()->json('qr');
        } catch (\Throwable) {
            return null;
        }
    }
}
