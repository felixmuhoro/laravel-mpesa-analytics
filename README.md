# felixmuhoro/laravel-mpesa-analytics

Analytics, reporting, and insights for M-Pesa transactions in Laravel.

## Requirements

- PHP 8.1+
- Laravel 10 / 11 / 12 / 13
- `felixmuhoro/laravel-mpesa: ^1.2`

## Installation

```bash
composer require felixmuhoro/laravel-mpesa-analytics
```

Publish the config and run migrations:

```bash
php artisan vendor:publish --tag=mpesa-analytics-config
php artisan vendor:publish --tag=mpesa-analytics-migrations
php artisan migrate
```

## Dashboard

Navigate to `/mpesa-analytics` in your browser. The dashboard shows:

- KPI cards: total revenue, average/median transaction, success rate, failed count, revenue growth
- Revenue over time (line chart)
- Success rate gauge (doughnut)
- Transaction volume — successful vs failed (stacked bar)
- Hourly pattern (bar chart, 0-23)
- Top 10 payers table
- Failure reasons pie chart

Date range picker supports: Today, Yesterday, Last 7/30/90 days, This/Last week, This/Last month, This year, and fully custom date ranges.

## Programmatic Usage

```php
use FelixMuhoro\MpesaAnalytics\Facades\Analytics;
use FelixMuhoro\MpesaAnalytics\Period;

$period = Period::last30Days();

// Revenue
$revenue = Analytics::revenue($period);
// => ['total' => 1250000.00, 'average' => 1500.00, 'median' => 1200.00,
//    'growth_percent' => 12.5, 'growth_direction' => 'up', ...]

// Transactions
$txns = Analytics::transactions($period);
// => ['total' => 833, 'successful' => 791, 'failed' => 42, 'success_rate' => 94.96, ...]

// Success rate
$rate = Analytics::successRate($period);   // float: 94.96

// Revenue over time (for charting)
$daily = Analytics::dailyRevenue($period);
// => [['date' => '2024-01-01', 'total' => 45000.0, 'count' => 30], ...]

// Hourly pattern
$hourly = Analytics::hourlyPattern($period);
// => [['hour' => 0, 'label' => '00:00', 'total' => 0, 'count' => 0], ...] (24 items)

// Top payers
$byAmount = Analytics::topPayersByAmount($period, limit: 10);
$byCount  = Analytics::topPayersByCount($period, limit: 10);

// Failure reasons (by M-Pesa result code)
$failures = Analytics::failureReasons($period);
// => [['code' => 1, 'description' => 'Insufficient funds', 'count' => 28], ...]

// New vs returning customers
$split = Analytics::newVsReturning($period);
// => ['new' => 120, 'returning' => 671, 'new_percent' => 15.2, ...]

// Full summary (all metrics in one call)
$summary = Analytics::summary($period);

// Custom period
$custom = Period::custom('2024-01-01', '2024-01-31');
$revenue = Analytics::revenue($custom);
```

## Period Helper

```php
Period::today()
Period::yesterday()
Period::thisWeek()
Period::lastWeek()
Period::thisMonth()
Period::lastMonth()
Period::last7Days()
Period::last30Days()
Period::last90Days()
Period::thisYear()
Period::custom('2024-01-01', '2024-03-31')
```

## Listening to Events

The package auto-discovers events from `felixmuhoro/laravel-mpesa`. You can also wire your own events in `config/mpesa-analytics.php`:

```php
'listen' => [
    'App\Events\PaymentSuccessful' => true,
    'App\Events\PaymentFailed'     => true,
],
```

Each event must carry a `transaction` property (array or object) with fields matching M-Pesa callback payload keys.

## CSV Export

```
GET /mpesa-analytics/export/csv?range=last_30_days
GET /mpesa-analytics/export/summary?range=this_month
GET /mpesa-analytics/export/csv?from=2024-01-01&to=2024-01-31
```

## Configuration

See `config/mpesa-analytics.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `route_prefix` | `mpesa-analytics` | URL prefix |
| `route_middleware` | `['web']` | Middleware stack |
| `default_range` | `last_30_days` | Default dashboard period |
| `currency_symbol` | `KSh` | Symbol shown in UI |
| `mask_phones` | `true` | Mask phone numbers on dashboard |
| `cache_ttl` | `300` | Seconds to cache aggregated metrics |
| `auto_discover_events` | `true` | Auto-wire laravel-mpesa events |

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/mpesa-analytics` | `mpesa-analytics.dashboard` | Dashboard HTML |
| GET | `/mpesa-analytics/data` | `mpesa-analytics.data` | JSON chart data |
| GET | `/mpesa-analytics/export/csv` | `mpesa-analytics.export.csv` | Transaction CSV |
| GET | `/mpesa-analytics/export/summary` | `mpesa-analytics.export.summary` | Summary CSV |

## Testing

```bash
composer test
```

## License

MIT — Felix Muhoro <hi@felixmuhoro.dev>