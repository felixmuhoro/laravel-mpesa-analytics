<?php

namespace FelixMuhoro\MpesaAnalytics;

use Illuminate\Support\ServiceProvider;
use FelixMuhoro\MpesaAnalytics\Analytics;
use FelixMuhoro\MpesaAnalytics\Metrics\RevenueMetric;
use FelixMuhoro\MpesaAnalytics\Metrics\TransactionMetric;
use FelixMuhoro\MpesaAnalytics\Metrics\PayerMetric;

class MpesaAnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mpesa-analytics.php', 'mpesa-analytics');

        $this->app->singleton(RevenueMetric::class);
        $this->app->singleton(TransactionMetric::class);
        $this->app->singleton(PayerMetric::class);

        $this->app->singleton(Analytics::class, fn ($app) => new Analytics(
            $app->make(RevenueMetric::class),
            $app->make(TransactionMetric::class),
            $app->make(PayerMetric::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mpesa-analytics.php' => config_path('mpesa-analytics.php'),
            ], 'mpesa-analytics-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'mpesa-analytics-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/mpesa-analytics'),
            ], 'mpesa-analytics-views');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mpesa-analytics');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        $dispatcher = $this->app->make('events');
        $listener   = \FelixMuhoro\MpesaAnalytics\Listeners\RecordTransactionMetric::class;

        // Auto-discover felixmuhoro/laravel-mpesa events if they exist
        $autoDiscover = config('mpesa-analytics.auto_discover_events', true);
        if ($autoDiscover) {
            foreach ([
                'FelixMuhoro\Mpesa\Events\PaymentSuccessful',
                'FelixMuhoro\Mpesa\Events\PaymentFailed',
                'FelixMuhoro\Mpesa\Events\StkPushSuccessful',
                'FelixMuhoro\Mpesa\Events\StkPushFailed',
            ] as $event) {
                if (class_exists($event)) {
                    $dispatcher->listen($event, $listener);
                }
            }
        }

        // User-configured events
        foreach (config('mpesa-analytics.listen', []) as $event => $enabled) {
            if ($enabled) {
                $dispatcher->listen($event, $listener);
            }
        }
    }
}
