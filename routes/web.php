<?php

use Illuminate\Support\Facades\Route;
use FelixMuhoro\MpesaAnalytics\Http\Controllers\AnalyticsDashboardController;
use FelixMuhoro\MpesaAnalytics\Http\Controllers\AnalyticsExportController;

$prefix     = config('mpesa-analytics.route_prefix', 'mpesa-analytics');
$middleware = config('mpesa-analytics.route_middleware', ['web']);

Route::middleware($middleware)
     ->prefix($prefix)
     ->name('mpesa-analytics.')
     ->group(function () {

         // Dashboard
         Route::get('/', [AnalyticsDashboardController::class, 'index'])
              ->name('dashboard');

         // JSON data endpoint for charts
         Route::get('/data', [AnalyticsDashboardController::class, 'data'])
              ->name('data');

         // Exports
         Route::get('/export/csv', [AnalyticsExportController::class, 'csv'])
              ->name('export.csv');

         Route::get('/export/summary', [AnalyticsExportController::class, 'summary'])
              ->name('export.summary');
     });
