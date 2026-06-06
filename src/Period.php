<?php

namespace FelixMuhoro\MpesaAnalytics;

use Carbon\Carbon;
use InvalidArgumentException;

class Period
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $label = 'custom'
    ) {}

    public static function today(): static
    {
        return new static(Carbon::today(), Carbon::today()->endOfDay(), 'today');
    }

    public static function yesterday(): static
    {
        return new static(Carbon::yesterday(), Carbon::yesterday()->endOfDay(), 'yesterday');
    }

    public static function thisWeek(): static
    {
        return new static(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'this_week');
    }

    public static function lastWeek(): static
    {
        return new static(
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek(),
            'last_week'
        );
    }

    public static function thisMonth(): static
    {
        return new static(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'this_month');
    }

    public static function lastMonth(): static
    {
        return new static(
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
            'last_month'
        );
    }

    public static function last7Days(): static
    {
        return new static(Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay(), 'last_7_days');
    }

    public static function last30Days(): static
    {
        return new static(Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay(), 'last_30_days');
    }

    public static function last90Days(): static
    {
        return new static(Carbon::now()->subDays(89)->startOfDay(), Carbon::now()->endOfDay(), 'last_90_days');
    }

    public static function thisYear(): static
    {
        return new static(Carbon::now()->startOfYear(), Carbon::now()->endOfYear(), 'this_year');
    }

    public static function custom(Carbon|string $from, Carbon|string $to): static
    {
        $fromCarbon = $from instanceof Carbon ? $from : Carbon::parse($from);
        $toCarbon = $to instanceof Carbon ? $to : Carbon::parse($to);

        if ($fromCarbon->isAfter($toCarbon)) {
            throw new InvalidArgumentException('The from date must be before the to date.');
        }

        return new static($fromCarbon->startOfDay(), $toCarbon->endOfDay(), 'custom');
    }

    /**
     * Get the equivalent previous period (same duration, shifted back).
     */
    public function previous(): static
    {
        $duration = $this->from->diffInSeconds($this->to);
        return new static(
            $this->from->copy()->subSeconds($duration + 1)->startOfDay(),
            $this->from->copy()->subDay()->endOfDay(),
            'previous_' . $this->label
        );
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    public static function fromRequest(string $range = 'last_30_days', ?string $from = null, ?string $to = null): static
    {
        if ($from && $to) {
            return static::custom($from, $to);
        }

        return match ($range) {
            'today'       => static::today(),
            'yesterday'   => static::yesterday(),
            'this_week'   => static::thisWeek(),
            'last_week'   => static::lastWeek(),
            'this_month'  => static::thisMonth(),
            'last_month'  => static::lastMonth(),
            'last_7_days' => static::last7Days(),
            'last_90_days'=> static::last90Days(),
            'this_year'   => static::thisYear(),
            default       => static::last30Days(),
        };
    }
}
