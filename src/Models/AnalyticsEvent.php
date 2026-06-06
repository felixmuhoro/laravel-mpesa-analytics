<?php

namespace FelixMuhoro\MpesaAnalytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use FelixMuhoro\MpesaAnalytics\Period;

class AnalyticsEvent extends Model
{
    protected $table = 'mpesa_analytics_events';

    protected $fillable = [
        'event_type',
        'transaction_id',
        'phone_number',
        'amount',
        'currency',
        'result_code',
        'result_description',
        'payment_method',
        'account_reference',
        'business_short_code',
        'transaction_type',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'metadata'    => 'array',
        'occurred_at' => 'datetime',
    ];

    public $timestamps = true;

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('result_code', 0)->where('event_type', 'payment_successful');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('event_type', 'payment_failed');
    }

    public function scopeInPeriod(Builder $query, Period $period): Builder
    {
        return $query->whereBetween('occurred_at', [$period->from, $period->to]);
    }

    public function scopeForPhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone_number', $phone);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->event_type === 'payment_successful' && $this->result_code === 0;
    }

    public function isFailed(): bool
    {
        return $this->event_type === 'payment_failed';
    }
}
