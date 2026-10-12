<?php

namespace App\Contracts;

interface NotificationProvider
{
    public function send(string $recipient, string $message): string;

    public function status(): array;

    public function qr(): ?string;
}
