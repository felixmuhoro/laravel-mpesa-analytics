<?php

namespace FelixMuhoro\MpesaAnalytics\Metrics;

use Illuminate\Support\Facades\DB;
use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use FelixMuhoro\MpesaAnalytics\Period;

class TransactionMetric
{
    /**
     * Full transaction stats for a period.
     */
    public function stats(Period $period): array
    {
        $total      = AnalyticsEvent::inPeriod($period)->count();
        $successful = AnalyticsEvent::successful()->inPeriod($period)->count();
        $failed     = AnalyticsEvent::failed()->inPeriod($period)->count();

        $successRate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        return [
            'total'        => $total,
            'successful'   => $successful,
            'failed'       => $failed,
            'pending'      => $total - $successful - $failed,
            'success_rate' => $successRate,
            'failure_rate' => $failureRate,
        ];
    }

    /**
     * Failure breakdown by M-Pesa result code.
     */
    public function failureReasons(Period $period): array
    {
        return AnalyticsEvent::failed()
            ->inPeriod($period)
            ->selectRaw('result_code, result_description, COUNT(*) as count')
            ->groupBy('result_code', 'result_description')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'code'        => $r->result_code,
                'description' => $r->result_description ?: static::describeCode($r->result_code),
                'count'       => (int) $r->count,
            ])
            ->toArray();
    }

    /**
     * Volume per day for chart.
     */
    public function daily(Period $period): array
    {
        return AnalyticsEvent::inPeriod($period)
            ->selectRaw(
                'DATE(occurred_at) as date,
                 COUNT(*) as total,
                 SUM(CASE WHEN event_type = \'payment_successful\' AND result_code = 0 THEN 1 ELSE 0 END) as successful,
                 SUM(CASE WHEN event_type = \'payment_failed\' THEN 1 ELSE 0 END) as failed'
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'       => $r->date,
                'total'      => (int) $r->total,
                'successful' => (int) $r->successful,
                'failed'     => (int) $r->failed,
            ])
            ->toArray();
    }

    /**
     * Human-readable description for common M-Pesa result codes.
     */
    public static function describeCode(?int $code): string
    {
        return match ($code) {
            0    => 'Success',
            1    => 'Insufficient funds',
            2    => 'Less than minimum transaction value',
            3    => 'More than maximum transaction value',
            4    => 'Would exceed daily transfer limit',
            5    => 'Would exceed minimum balance',
            6    => 'Unresolved primary party',
            7    => 'Unresolved receiver party',
            8    => 'Would exceed maximum balance',
            11   => 'Debit account invalid',
            12   => 'Credit account invalid',
            13   => 'Unresolved debit account',
            14   => 'Unresolved credit account',
            15   => 'Duplicate detected',
            17   => 'Internal failure',
            20   => 'Unresolved initiator',
            26   => 'Traffic blocking condition in place',
            1001 => 'Unable to lock subscriber',
            1019 => 'Transaction expired in queue',
            1025 => 'Invalid initiator information',
            1032 => 'Request cancelled by user',
            1037 => 'DS timeout (user unreachable)',
            2001 => 'Wrong PIN entered',
            default => 'Error code ' . ($code ?? 'unknown'),
        };
    }
}
