<?php

namespace FelixMuhoro\MpesaAnalytics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use FelixMuhoro\MpesaAnalytics\Analytics;
use FelixMuhoro\MpesaAnalytics\Period;

class AnalyticsDashboardController extends Controller
{
    public function __construct(private readonly Analytics $analytics) {}

    /**
     * Render the analytics dashboard view.
     */
    public function index(Request $request)
    {
        $period = Period::fromRequest(
            $request->input('range', 'last_30_days'),
            $request->input('from'),
            $request->input('to'),
        );

        if ($request->wantsJson()) {
            return $this->json($period);
        }

        return view('mpesa-analytics::dashboard', [
            'period'  => $period,
            'ranges'  => $this->rangeOptions(),
        ]);
    }

    /**
     * JSON endpoint for chart data (called by dashboard JS).
     */
    public function data(Request $request): JsonResponse
    {
        $period = Period::fromRequest(
            $request->input('range', 'last_30_days'),
            $request->input('from'),
            $request->input('to'),
        );

        return response()->json($this->buildPayload($period));
    }

    private function json(Period $period): JsonResponse
    {
        return response()->json($this->buildPayload($period));
    }

    private function buildPayload(Period $period): array
    {
        return [
            'period' => [
                'from'  => $period->from->toDateString(),
                'to'    => $period->to->toDateString(),
                'label' => $period->label,
                'days'  => $period->days(),
            ],
            'revenue'            => $this->analytics->revenue($period),
            'transactions'       => $this->analytics->transactions($period),
            'daily_revenue'      => $this->analytics->dailyRevenue($period),
            'daily_transactions' => $this->analytics->dailyTransactions($period),
            'hourly_pattern'     => $this->analytics->hourlyPattern($period),
            'failure_reasons'    => $this->analytics->failureReasons($period),
            'top_payers'         => $this->analytics->topPayersByAmount($period, 10),
            'new_vs_returning'   => $this->analytics->newVsReturning($period),
        ];
    }

    private function rangeOptions(): array
    {
        return [
            'today'        => 'Today',
            'yesterday'    => 'Yesterday',
            'last_7_days'  => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'this_week'    => 'This week',
            'last_week'    => 'Last week',
            'this_month'   => 'This month',
            'last_month'   => 'Last month',
            'last_90_days' => 'Last 90 days',
            'this_year'    => 'This year',
        ];
    }
}
