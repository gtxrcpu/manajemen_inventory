<?php

namespace App\Providers;

use App\Contracts\NotificationProvider;
use App\Models\StockMovement;
use App\Observers\StockMovementObserver;
use App\Services\WhatsAppWebJsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationProvider::class, WhatsAppWebJsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StockMovement::observe(StockMovementObserver::class);
    }
}
