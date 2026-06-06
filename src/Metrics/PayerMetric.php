<?php

namespace FelixMuhoro\MpesaAnalytics\Metrics;

use Illuminate\Support\Facades\DB;
use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use FelixMuhoro\MpesaAnalytics\Period;

class PayerMetric
{
    /**
     * Top N payers by total amount paid.
     */
    public function topByAmount(Period $period, int $limit = 10): array
    {
        return AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('phone_number, SUM(amount) as total_amount, COUNT(*) as transaction_count, AVG(amount) as avg_amount')
            ->groupBy('phone_number')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'phone'             => $r->phone_number,
                'total_amount'      => (float) $r->total_amount,
                'transaction_count' => (int) $r->transaction_count,
                'avg_amount'        => round((float) $r->avg_amount, 2),
            ])
            ->toArray();
    }

    /**
     * Top N payers by number of successful transactions.
     */
    public function topByCount(Period $period, int $limit = 10): array
    {
        return AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('phone_number, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->groupBy('phone_number')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'phone'             => $r->phone_number,
                'transaction_count' => (int) $r->transaction_count,
                'total_amount'      => (float) $r->total_amount,
            ])
            ->toArray();
    }

    /**
     * New vs returning customers in the period.
     */
    public function newVsReturning(Period $period): array
    {
        // A payer is "new" if their first-ever successful transaction falls within this period.
        $allPayers = AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('phone_number, MIN(occurred_at) as first_in_period')
            ->groupBy('phone_number')
            ->get();

        $returning = 0;
        $new = 0;

        foreach ($allPayers as $payer) {
            $hadPrior = AnalyticsEvent::successful()
                ->where('phone_number', $payer->phone_number)
                ->where('occurred_at', '<', $period->from)
                ->exists();

            $hadPrior ? $returning++ : $new++;
        }

        $total = $new + $returning;

        return [
            'new'             => $new,
            'returning'       => $returning,
            'total_unique'    => $total,
            'new_percent'     => $total > 0 ? round(($new / $total) * 100, 2) : 0,
            'returning_percent' => $total > 0 ? round(($returning / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Repeat customers: payers with more than one transaction in period.
     */
    public function repeatCustomers(Period $period): array
    {
        $rows = AnalyticsEvent::successful()
            ->inPeriod($period)
            ->selectRaw('phone_number, COUNT(*) as cnt')
            ->groupBy('phone_number')
            ->havingRaw('cnt > 1')
            ->orderByDesc('cnt')
            ->get();

        return [
            'count' => $rows->count(),
            'payers' => $rows->map(fn ($r) => [
                'phone' => $r->phone_number,
                'transactions' => (int) $r->cnt,
            ])->toArray(),
        ];
    }
}
