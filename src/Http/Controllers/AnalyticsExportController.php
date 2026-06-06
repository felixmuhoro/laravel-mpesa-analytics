<?php

namespace FelixMuhoro\MpesaAnalytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use FelixMuhoro\MpesaAnalytics\Analytics;
use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use FelixMuhoro\MpesaAnalytics\Period;

class AnalyticsExportController extends Controller
{
    public function __construct(private readonly Analytics $analytics) {}

    /**
     * Export transactions as CSV.
     */
    public function csv(Request $request): Response
    {
        $period = Period::fromRequest(
            $request->input('range', 'last_30_days'),
            $request->input('from'),
            $request->input('to'),
        );

        $rows = AnalyticsEvent::inPeriod($period)
            ->orderBy('occurred_at', 'desc')
            ->get();

        $filename = 'mpesa-transactions-' . $period->from->toDateString() . '-to-' . $period->to->toDateString() . '.csv';

        $csv  = $this->buildCsv($rows);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename= . \$filename . ',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Export summary metrics as CSV.
     */
    public function summary(Request $request): Response
    {
        $period = Period::fromRequest(
            $request->input('range', 'last_30_days'),
            $request->input('from'),
            $request->input('to'),
        );

        $data     = $this->analytics->summary($period);
        $filename = 'mpesa-summary-' . $period->from->toDateString() . '-to-' . $period->to->toDateString() . '.csv';

        $lines   = [];
        $lines[] = implode(',', ['Metric', 'Value']);
        $lines[] = implode(',', ['Period From', $period->from->toDateString()]);
        $lines[] = implode(',', ['Period To', $period->to->toDateString()]);
        $lines[] = '';
        $lines[] = implode(',', ['Total Revenue (KES)', $data['revenue']['total']]);
        $lines[] = implode(',', ['Average Transaction (KES)', $data['revenue']['average']]);
        $lines[] = implode(',', ['Median Transaction (KES)', $data['revenue']['median']]);
        $lines[] = implode(',', ['Revenue Growth %', $data['revenue']['growth_percent']]);
        $lines[] = '';
        $lines[] = implode(',', ['Total Transactions', $data['transactions']['total']]);
        $lines[] = implode(',', ['Successful', $data['transactions']['successful']]);
        $lines[] = implode(',', ['Failed', $data['transactions']['failed']]);
        $lines[] = implode(',', ['Success Rate %', $data['transactions']['success_rate']]);

        return response(implode("\n", $lines), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename= . \$filename . ',
        ]);
    }

    private function buildCsv($rows): string
    {
        $handle = fopen('php://memory', 'r+');

        // Header
        fputcsv($handle, [
            'ID', 'Event Type', 'Transaction ID', 'Phone Number',
            'Amount', 'Currency', 'Result Code', 'Result Description',
            'Payment Method', 'Account Reference', 'Business Short Code',
            'Transaction Type', 'Occurred At', 'Created At',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->id,
                $row->event_type,
                $row->transaction_id,
                $row->phone_number,
                $row->amount,
                $row->currency,
                $row->result_code,
                $row->result_description,
                $row->payment_method,
                $row->account_reference,
                $row->business_short_code,
                $row->transaction_type,
                $row->occurred_at?->toDateTimeString(),
                $row->created_at?->toDateTimeString(),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
