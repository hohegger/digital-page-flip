<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Validity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class ValidityCalculator
{
    public const DEFAULT_PERIOD_DAYS = 14;

    /**
     * Online start: 00:00 of the day (validFrom - leadDays) in the given time zone.
     */
    public static function onlineStart(int $validFrom, int $leadDays, DateTimeZone $timezone): int
    {
        return self::midnight($validFrom, $timezone)
            ->modify(sprintf('-%d days', $leadDays))
            ->getTimestamp();
    }

    /**
     * Default end: 00:00 of the day validFrom + 13 days (two-week period).
     */
    public static function defaultValidUntil(int $validFrom, DateTimeZone $timezone): int
    {
        return self::midnight($validFrom, $timezone)
            ->modify(sprintf('+%d days', self::DEFAULT_PERIOD_DAYS - 1))
            ->getTimestamp();
    }

    /**
     * Calendar week label "<ISO week of validFrom>_<ISO week of validUntil - 6 days>", two digits each.
     * Only the first week is returned for periods shorter than seven days or when both weeks are equal.
     */
    public static function weekLabel(DateTimeInterface $validFrom, ?DateTimeInterface $validUntil): string
    {
        $first = $validFrom->format('W');
        if ($validUntil === null) {
            return $first;
        }

        $secondWeekDay = DateTimeImmutable::createFromInterface($validUntil)->modify('-6 days');
        if ($secondWeekDay->format('Y-m-d') < $validFrom->format('Y-m-d')) {
            return $first;
        }

        $second = $secondWeekDay->format('W');

        return $second === $first ? $first : $first . '_' . $second;
    }

    private static function midnight(int $timestamp, DateTimeZone $timezone): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($timezone)
            ->setTime(0, 0);
    }
}
