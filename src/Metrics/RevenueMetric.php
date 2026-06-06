<?php

namespace FelixMuhoro\MpesaAnalytics\Metrics;

use Illuminate\Support\Facades\DB;
use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use FelixMuhoro\MpesaAnalytics\Period;

class RevenueMetric
{
    public function __construct(private readonly string $table = 'mpesa_analytics_events') {}

    /**
     * Full revenue stats for a period including growth vs previous period.
     */
    public function stats(Period $period): array
    {
        $current  = $this->aggregate($period);
        $previous = $this->aggregate($period->previous());

        $growth = 0.0;
        if ($previous['total'] > 0) {
            $growth = (($current['total'] - $previous['total']) / $previous['total']) * 100;
        }

        return array_merge($current, [
            'previous_total' => $previous['total'],
            'growth_percent' => round($growth, 2),
            'growth_direction' => $growth >= 0 ? 'up' : 'down',
        ]);
    }

    private function aggregate(Period $period): array
    {
        $rows = AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('amount')
            ->orderBy('amount')
            ->pluck('amount')
            ->map(fn ($v) => (float) $v)
            ->values();

        if ($rows->isEmpty()) {
            return ['total' => 0, 'average' => 0, 'median' => 0, 'count' => 0, 'min' => 0, 'max' => 0];
        }

        $count  = $rows->count();
        $total  = $rows->sum();
        $avg    = $total / $count;
        $median = $count % 2 === 0
            ? ($rows[$count / 2 - 1] + $rows[$count / 2]) / 2
            : $rows[(int) ($count / 2)];

        return [
            'total'   => round($total, 2),
            'average' => round($avg, 2),
            'median'  => round($median, 2),
            'count'   => $count,
            'min'     => $rows->first(),
            'max'     => $rows->last(),
        ];
    }

    /**
     * Daily revenue breakdown for charting.
     */
    public function daily(Period $period): array
    {
        return AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('DATE(occurred_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => $r->date,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    /**
     * Hourly revenue pattern (aggregated across the period).
     */
    public function hourly(Period $period): array
    {
        $raw = AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('HOUR(occurred_at) as hour, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        return collect(range(0, 23))->map(function (int $h) use ($raw) {
            $row = $raw->get($h);
            return [
                'hour'  => $h,
                'label' => sprintf('%02d:00', $h),
                'total' => $row ? (float) $row->total : 0,
                'count' => $row ? (int) $row->count : 0,
            ];
        })->values()->toArray();
    }
}
