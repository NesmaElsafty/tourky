<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Business week: Sunday through Thursday (Fri–Sat are between weeks).
 */
final class OperationalWeek
{
    /**
     * @return array{start: Carbon, end: Carbon, offset: int}
     */
    public static function bounds(?Carbon $anchor = null, int $offset = 0): array
    {
        $anchor = ($anchor ?? now())->copy()->startOfDay();

        if (in_array($anchor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true)) {
            $start = $anchor->copy()->next(Carbon::SUNDAY);
        } else {
            $start = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        }

        if ($offset !== 0) {
            $start->addDays(7 * $offset);
        }

        $end = $start->copy()->addDays(4);

        return [
            'start' => $start,
            'end' => $end,
            'offset' => $offset,
        ];
    }

    /**
     * @return array{offset: int, start_date: string, end_date: string}
     */
    public static function meta(int $offset = 0, ?Carbon $anchor = null): array
    {
        $bounds = self::bounds($anchor, $offset);

        return [
            'offset' => $bounds['offset'],
            'start_date' => $bounds['start']->toDateString(),
            'end_date' => $bounds['end']->toDateString(),
        ];
    }
}
