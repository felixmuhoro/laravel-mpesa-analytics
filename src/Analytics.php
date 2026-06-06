<?php

namespace FelixMuhoro\MpesaAnalytics;

use FelixMuhoro\MpesaAnalytics\Metrics\PayerMetric;
use FelixMuhoro\MpesaAnalytics\Metrics\RevenueMetric;
use FelixMuhoro\MpesaAnalytics\Metrics\TransactionMetric;

class Analytics
{
    public function __construct(
        private readonly RevenueMetric     $revenue,
        private readonly TransactionMetric $transactions,
        private readonly PayerMetric       $payers,
    ) {}

    // ── Revenue ──────────────────────────────────────────────────────────────

    /**
     * Revenue stats (total, average, median, growth) for a period.
     */
    public function revenue(Period $period): array
    {
        return $this->revenue->stats($period);
    }

    /**
     * Daily revenue breakdown for charting.
     */
    public function dailyRevenue(Period $period): array
    {
        return $this->revenue->daily($period);
    }

    /**
     * Hourly revenue pattern (0-23) aggregated over the period.
     */
    public function hourlyPattern(Period $period): array
    {
        return $this->revenue->hourly($period);
    }

    // ── Transactions ─────────────────────────────────────────────────────────

    /**
     * Transaction count, success rate, failure rate for a period.
     */
    public function transactions(Period $period): array
    {
        return $this->transactions->stats($period);
    }

    /**
     * Success rate as a decimal (0–100).
     */
    public function successRate(Period $period): float
    {
        return $this->transactions->stats($period)['success_rate'];
    }

    /**
     * Failure reasons broken down by M-Pesa result code.
     */
    public function failureReasons(Period $period): array
    {
        return $this->transactions->failureReasons($period);
    }

    /**
     * Daily transaction volume for charting.
     */
    public function dailyTransactions(Period $period): array
    {
        return $this->transactions->daily($period);
    }

    // ── Payers ───────────────────────────────────────────────────────────────

    /**
     * Top N payers ranked by total amount paid.
     */
    public function topPayersByAmount(Period $period, int $limit = 10): array
    {
        return $this->payers->topByAmount($period, $limit);
    }

    /**
     * Top N payers ranked by number of transactions.
     */
    public function topPayersByCount(Period $period, int $limit = 10): array
    {
        return $this->payers->topByCount($period, $limit);
    }

    /**
     * New vs returning customer split.
     */
    public function newVsReturning(Period $period): array
    {
        return $this->payers->newVsReturning($period);
    }

    /**
     * Repeat customers (more than one transaction) in the period.
     */
    public function repeatCustomers(Period $period): array
    {
        return $this->payers->repeatCustomers($period);
    }

    // ── Convenience ──────────────────────────────────────────────────────────

    /**
     * Full dashboard summary – all key metrics in one call.
     */
    public function summary(Period $period): array
    {
        return [
            'period'       => ['from' => $period->from->toDateString(), 'to' => $period->to->toDateString(), 'label' => $period->label],
            'revenue'      => $this->revenue($period),
            'transactions' => $this->transactions($period),
            'payers'       => [
                'top_by_amount' => $this->topPayersByAmount($period, 5),
                'new_vs_returning' => $this->newVsReturning($period),
            ],
            'failure_reasons' => $this->failureReasons($period),
        ];
    }
}
