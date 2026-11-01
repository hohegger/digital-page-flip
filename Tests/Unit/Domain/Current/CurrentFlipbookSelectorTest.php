<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Domain\Current;

use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbookSelector;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CurrentFlipbookSelectorTest extends TestCase
{
    private const NOW = 1_800_000_000;
    private const DAY = 86_400;

    #[Test]
    public function selectorPicksLatestStartedFlipbook(): void
    {
        $rows = [
            $this->row(1, self::NOW - 20 * self::DAY),
            $this->row(2, self::NOW - 6 * self::DAY),
            $this->row(3, self::NOW - 34 * self::DAY),
        ];

        self::assertSame(2, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorIgnoresFutureOnlineStart(): void
    {
        $rows = [
            $this->row(1, self::NOW - 10 * self::DAY),
            $this->row(2, self::NOW + 1),
        ];

        self::assertSame(1, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorAcceptsOnlineStartEqualToNow(): void
    {
        $rows = [
            $this->row(1, self::NOW - 10 * self::DAY),
            $this->row(2, self::NOW),
        ];

        self::assertSame(2, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorIgnoresNotCompletedFlipbooks(): void
    {
        $rows = [
            $this->row(1, self::NOW - 14 * self::DAY),
            $this->row(2, self::NOW - self::DAY, Flipbook::STATUS_PENDING),
            $this->row(3, self::NOW - self::DAY, Flipbook::STATUS_PROCESSING),
            $this->row(4, self::NOW - self::DAY, Flipbook::STATUS_ERROR),
        ];

        self::assertSame(1, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorIgnoresRecordsWithoutValidFrom(): void
    {
        $rows = [
            $this->row(1, self::NOW - 14 * self::DAY),
            $this->row(2, self::NOW - self::DAY, Flipbook::STATUS_COMPLETED, 0),
        ];

        self::assertSame(1, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorBreaksTieByHighestUid(): void
    {
        $start = self::NOW - 3 * self::DAY;
        $rows = [
            $this->row(5, $start),
            $this->row(9, $start),
            $this->row(7, $start),
        ];

        self::assertSame(9, CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorKeepsExpiredFlipbookWithoutSuccessor(): void
    {
        $row = $this->row(1, self::NOW - 40 * self::DAY);
        $row['valid_until'] = self::NOW - 25 * self::DAY;

        self::assertSame(1, CurrentFlipbookSelector::selectCurrent([$row], self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function selectorReturnsNullForEmptyList(): void
    {
        self::assertNull(CurrentFlipbookSelector::selectCurrent([], self::NOW));
    }

    #[Test]
    public function selectorAcceptsDatabaseStringValues(): void
    {
        $rows = [
            ['uid' => '3', 'starttime' => (string) (self::NOW - self::DAY), 'valid_from' => (string) self::NOW, 'conversion_status' => '2'],
        ];

        self::assertSame('3', CurrentFlipbookSelector::selectCurrent($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function upcomingPicksEarliestFutureStart(): void
    {
        $rows = [
            $this->row(1, self::NOW - self::DAY),
            $this->row(2, self::NOW + 20 * self::DAY),
            $this->row(3, self::NOW + 6 * self::DAY, Flipbook::STATUS_PENDING),
            $this->row(4, self::NOW + 34 * self::DAY),
        ];

        self::assertSame(3, CurrentFlipbookSelector::selectUpcoming($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function upcomingIgnoresPastStarts(): void
    {
        $rows = [
            $this->row(1, self::NOW - self::DAY),
            $this->row(2, self::NOW),
        ];

        self::assertNull(CurrentFlipbookSelector::selectUpcoming($rows, self::NOW));
    }

    #[Test]
    public function upcomingIgnoresRecordsWithoutValidFrom(): void
    {
        $rows = [
            $this->row(1, self::NOW + self::DAY, Flipbook::STATUS_COMPLETED, 0),
            $this->row(2, self::NOW + 5 * self::DAY),
        ];

        self::assertSame(2, CurrentFlipbookSelector::selectUpcoming($rows, self::NOW)['uid'] ?? null);
    }

    #[Test]
    public function upcomingBreaksTieByHighestUid(): void
    {
        $start = self::NOW + 2 * self::DAY;
        $rows = [
            $this->row(4, $start),
            $this->row(8, $start),
        ];

        self::assertSame(8, CurrentFlipbookSelector::selectUpcoming($rows, self::NOW)['uid'] ?? null);
    }

    /**
     * @return array<string, int>
     */
    private function row(
        int $uid,
        int $starttime,
        int $conversionStatus = Flipbook::STATUS_COMPLETED,
        ?int $validFrom = null,
    ): array {
        return [
            'uid' => $uid,
            'starttime' => $starttime,
            'valid_from' => $validFrom ?? $starttime + 2 * self::DAY,
            'conversion_status' => $conversionStatus,
        ];
    }
}
