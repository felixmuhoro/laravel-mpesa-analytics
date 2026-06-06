<?php

namespace FelixMuhoro\MpesaAnalytics\Tests;

use Orchestra\Testbench\TestCase;
use FelixMuhoro\MpesaAnalytics\MpesaAnalyticsServiceProvider;
use FelixMuhoro\MpesaAnalytics\Analytics;
use FelixMuhoro\MpesaAnalytics\Period;
use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [MpesaAnalyticsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function test_period_today(): void
    {
        $period = Period::today();
        $this->assertSame('today', $period->label);
        $this->assertEquals(Carbon::today()->toDateString(), $period->from->toDateString());
    }

    public function test_period_last_30_days(): void
    {
        $period = Period::last30Days();
        $this->assertEquals(30, $period->days());
    }

    public function test_period_custom(): void
    {
        $period = Period::custom('2024-01-01', '2024-01-31');
        $this->assertEquals('2024-01-01', $period->from->toDateString());
        $this->assertEquals('2024-01-31', $period->to->toDateString());
        $this->assertEquals(31, $period->days());
    }

    public function test_period_custom_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Period::custom('2024-01-31', '2024-01-01');
    }

    public function test_period_previous(): void
    {
        $period   = Period::custom('2024-02-01', '2024-02-29');
        $previous = $period->previous();
        $this->assertTrue($previous->to->lt($period->from));
    }

    public function test_period_from_request_default(): void
    {
        $period = Period::fromRequest();
        $this->assertSame('last_30_days', $period->label);
    }

    public function test_analytics_event_can_be_created(): void
    {
        $event = AnalyticsEvent::create([
            'event_type'     => 'payment_successful',
            'phone_number'   => '254712345678',
            'amount'         => 1500.00,
            'currency'       => 'KES',
            'result_code'    => 0,
            'payment_method' => 'mpesa',
            'occurred_at'    => now(),
        ]);

        $this->assertDatabaseHas('mpesa_analytics_events', ['phone_number' => '254712345678']);
        $this->assertTrue($event->isSuccessful());
    }

    public function test_successful_scope(): void
    {
        $this->seedEvents();
        $this->assertEquals(3, AnalyticsEvent::successful()->count());
    }

    public function test_failed_scope(): void
    {
        $this->seedEvents();
        $this->assertEquals(2, AnalyticsEvent::failed()->count());
    }

    public function test_in_period_scope(): void
    {
        $this->seedEvents();
        $period = Period::custom('2024-01-01', '2024-01-31');
        $this->assertEquals(5, AnalyticsEvent::inPeriod($period)->count());
    }

    public function test_revenue_stats(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $stats     = $analytics->revenue($period);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('average', $stats);
        $this->assertArrayHasKey('median', $stats);
        $this->assertArrayHasKey('growth_percent', $stats);
        $this->assertEquals(4500.00, $stats['total']);
    }

    public function test_transactions_stats(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $stats     = $analytics->transactions($period);

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(3, $stats['successful']);
        $this->assertEquals(2, $stats['failed']);
        $this->assertEquals(60.0, $stats['success_rate']);
    }

    public function test_success_rate(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $this->assertEquals(60.0, $analytics->successRate($period));
    }

    public function test_top_payers_by_amount(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $payers    = $analytics->topPayersByAmount($period);

        $this->assertNotEmpty($payers);
        $this->assertArrayHasKey('phone', $payers[0]);
        $this->assertArrayHasKey('total_amount', $payers[0]);
        $this->assertGreaterThanOrEqual(
            $payers[count($payers) - 1]['total_amount'],
            $payers[0]['total_amount']
        );
    }

    public function test_top_payers_by_count(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $payers    = $analytics->topPayersByCount($period);

        $this->assertNotEmpty($payers);
        $this->assertArrayHasKey('transaction_count', $payers[0]);
    }

    public function test_failure_reasons(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $reasons   = $analytics->failureReasons($period);

        $this->assertNotEmpty($reasons);
        $this->assertArrayHasKey('code', $reasons[0]);
        $this->assertArrayHasKey('description', $reasons[0]);
        $this->assertArrayHasKey('count', $reasons[0]);
    }

    public function test_daily_revenue(): void
    {
        $this->seedEvents();
        $analytics = $this->app->make(Analytics::class);
        $period    = Period::custom('2024-01-01', '2024-01-31');
        $daily     = $analytics->dailyRevenue($period);

        $this->assertIsArray($daily);
        if (!empty($daily)) {
            $this->assertArrayHasKey('date', $daily[0]);
            $this->assertArrayHasKey('total', $daily[0]);
        }
    }

    public function test_hourly_pattern_has_24_slots(): void
    {
        $analytics = $this->app->make(Analytics::class);
        $hourly    = $analytics->hourlyPattern(Period::last30Days());

        $this->assertCount(24, $hourly);
        $this->assertEquals('00:00', $hourly[0]['label']);
        $this->assertEquals('23:00', $hourly[23]['label']);
    }

    public function test_summary_contains_all_sections(): void
    {
        $analytics = $this->app->make(Analytics::class);
        $summary   = $analytics->summary(Period::last30Days());

        $this->assertArrayHasKey('period', $summary);
        $this->assertArrayHasKey('revenue', $summary);
        $this->assertArrayHasKey('transactions', $summary);
        $this->assertArrayHasKey('payers', $summary);
        $this->assertArrayHasKey('failure_reasons', $summary);
    }

    private function seedEvents(): void
    {
        $base = ['currency' => 'KES', 'payment_method' => 'mpesa'];
        $jan  = '2024-01-15 10:00:00';

        AnalyticsEvent::create(array_merge($base, ['event_type' => 'payment_successful', 'phone_number' => '254700000001', 'amount' => 2000, 'result_code' => 0,    'occurred_at' => $jan]));
        AnalyticsEvent::create(array_merge($base, ['event_type' => 'payment_successful', 'phone_number' => '254700000002', 'amount' => 1500, 'result_code' => 0,    'occurred_at' => $jan]));
        AnalyticsEvent::create(array_merge($base, ['event_type' => 'payment_successful', 'phone_number' => '254700000001', 'amount' => 1000, 'result_code' => 0,    'occurred_at' => $jan]));
        AnalyticsEvent::create(array_merge($base, ['event_type' => 'payment_failed',     'phone_number' => '254700000003', 'amount' => 500,  'result_code' => 1,    'result_description' => 'Insufficient funds',       'occurred_at' => $jan]));
        AnalyticsEvent::create(array_merge($base, ['event_type' => 'payment_failed',     'phone_number' => '254700000004', 'amount' => 750,  'result_code' => 1032, 'result_description' => 'Request cancelled by user', 'occurred_at' => $jan]));
    }
}