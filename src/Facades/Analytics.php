<?php

namespace FelixMuhoro\MpesaAnalytics\Facades;

use Illuminate\Support\Facades\Facade;
use FelixMuhoro\MpesaAnalytics\Analytics as AnalyticsService;

/**
 * @method static array revenue(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array dailyRevenue(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array hourlyPattern(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array transactions(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static float successRate(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array failureReasons(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array dailyTransactions(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array topPayersByAmount(\FelixMuhoro\MpesaAnalytics\Period $period, int $limit = 10)
 * @method static array topPayersByCount(\FelixMuhoro\MpesaAnalytics\Period $period, int $limit = 10)
 * @method static array newVsReturning(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array repeatCustomers(\FelixMuhoro\MpesaAnalytics\Period $period)
 * @method static array summary(\FelixMuhoro\MpesaAnalytics\Period $period)
 *
 * @see \FelixMuhoro\MpesaAnalytics\Analytics
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyticsService::class;
    }
}
