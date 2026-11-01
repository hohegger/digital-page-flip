<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Current;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;

/**
 * Pure selection rules for the automatically published flipbook.
 *
 * Rows need at least: uid, starttime (= online start), valid_from, conversion_status.
 * Hidden/deleted records are expected to be filtered out by the database query already.
 */
final class CurrentFlipbookSelector
{
    /**
     * Current flipbook: latest online start <= now among converted records with a validity start.
     * Ties are broken by the highest uid. "valid until" does not end the publication.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>|null
     */
    public static function selectCurrent(array $rows, int $now): ?array
    {
        $selected = null;
        foreach ($rows as $row) {
            if (!self::hasValidity($row)
                || (int) ($row['conversion_status'] ?? -1) !== Flipbook::STATUS_COMPLETED
                || self::starttime($row) > $now
            ) {
                continue;
            }
            if ($selected === null || self::compare($row, $selected) > 0) {
                $selected = $row;
            }
        }

        return $selected;
    }

    /**
     * Upcoming flipbook: earliest online start > now among records with a validity start,
     * regardless of the conversion state. Ties are broken by the highest uid.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>|null
     */
    public static function selectUpcoming(array $rows, int $now): ?array
    {
        $selected = null;
        foreach ($rows as $row) {
            if (!self::hasValidity($row) || self::starttime($row) <= $now) {
                continue;
            }
            if ($selected === null
                || self::starttime($row) < self::starttime($selected)
                || (self::starttime($row) === self::starttime($selected) && self::uid($row) > self::uid($selected))
            ) {
                $selected = $row;
            }
        }

        return $selected;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function hasValidity(array $row): bool
    {
        return (int) ($row['valid_from'] ?? 0) > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function starttime(array $row): int
    {
        return (int) ($row['starttime'] ?? 0);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function uid(array $row): int
    {
        return (int) ($row['uid'] ?? 0);
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function compare(array $a, array $b): int
    {
        return [self::starttime($a), self::uid($a)] <=> [self::starttime($b), self::uid($b)];
    }
}
