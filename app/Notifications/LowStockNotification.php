<?php

namespace App\Notifications;

use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LowStockAlert $alert) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        $this->alert->loadMissing(['product', 'location']);
        return [
            'alert_id' => $this->alert->id,
            'product_id' => $this->alert->product_id,
            'product' => $this->alert->product->name,
            'sku' => $this->alert->product->sku,
            'location_id' => $this->alert->location_id,
            'location' => $this->alert->location->name,
            'stock_quantity' => (float) $this->alert->stock_quantity,
            'minimum_quantity' => (float) $this->alert->minimum_quantity,
            'message' => 'Stok '.$this->alert->product->name.' di '.$this->alert->location->name.' berada di bawah batas minimum.',
            'url' => route('low-stock.index'),
            'triggered_at' => $this->alert->triggered_at->toIso8601String(),
        ];
    }
}
