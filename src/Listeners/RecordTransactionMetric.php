<?php

namespace FelixMuhoro\MpesaAnalytics\Listeners;

use FelixMuhoro\MpesaAnalytics\Models\AnalyticsEvent;
use Carbon\Carbon;

class RecordTransactionMetric
{
    /**
     * Handle PaymentSuccessful and PaymentFailed events from felixmuhoro/laravel-mpesa.
     * Both events are expected to carry a $transaction array/object.
     */
    public function handle(object $event): void
    {
        $tx = $this->extractTransaction($event);

        if ($tx === null) {
            return;
        }

        AnalyticsEvent::create([
            'event_type'          => $this->resolveEventType($event),
            'transaction_id'      => $tx['transaction_id'] ?? $tx['CheckoutRequestID'] ?? null,
            'phone_number'        => $this->normalizePhone($tx['phone_number'] ?? $tx['PhoneNumber'] ?? null),
            'amount'              => $tx['amount'] ?? $tx['Amount'] ?? 0,
            'currency'            => $tx['currency'] ?? 'KES',
            'result_code'         => $tx['result_code'] ?? $tx['ResultCode'] ?? null,
            'result_description'  => $tx['result_description'] ?? $tx['ResultDesc'] ?? null,
            'payment_method'      => $tx['payment_method'] ?? 'mpesa',
            'account_reference'   => $tx['account_reference'] ?? $tx['AccountReference'] ?? null,
            'business_short_code' => $tx['business_short_code'] ?? $tx['BusinessShortCode'] ?? config('mpesa-analytics.default_short_code'),
            'transaction_type'    => $tx['transaction_type'] ?? $tx['TransactionType'] ?? 'CustomerPayBillOnline',
            'metadata'            => $this->buildMetadata($tx),
            'occurred_at'         => $tx['created_at'] ?? $tx['TransTime'] ?? Carbon::now(),
        ]);
    }

    private function extractTransaction(object $event): ?array
    {
        foreach (['transaction', 'payload', 'data', 'result'] as $key) {
            if (isset($event->{$key})) {
                $val = $event->{$key};
                return is_array($val) ? $val : (array) $val;
            }
        }
        return null;
    }

    private function resolveEventType(object $event): string
    {
        $class = get_class($event);
        if (str_contains($class, 'Failed') || str_contains($class, 'Failure')) {
            return 'payment_failed';
        }
        return 'payment_successful';
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        // Strip leading + and ensure 254 prefix for Kenyan numbers
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        return $phone;
    }

    private function buildMetadata(array $tx): array
    {
        // Store all fields not already mapped to dedicated columns
        $reserved = ['transaction_id', 'CheckoutRequestID', 'phone_number', 'PhoneNumber',
                      'amount', 'Amount', 'currency', 'result_code', 'ResultCode',
                      'result_description', 'ResultDesc', 'payment_method', 'account_reference',
                      'AccountReference', 'business_short_code', 'BusinessShortCode',
                      'transaction_type', 'TransactionType', 'created_at', 'TransTime'];
        return array_diff_key($tx, array_flip($reserved));
    }
}
