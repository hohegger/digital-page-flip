<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Domain\Validity;

use DateTimeImmutable;
use DateTimeZone;
use Kit\DigitalPageFlip\Domain\Validity\ValidityCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidityCalculatorTest extends TestCase
{
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->timezone = new DateTimeZone('Europe/Berlin');
    }

    #[Test]
    public function onlineStartSubtractsLeadDaysAtMidnight(): void
    {
        $validFrom = $this->timestamp('2026-10-28 12:00');

        self::assertSame(
            $this->timestamp('2026-10-26 00:00'),
            ValidityCalculator::onlineStart($validFrom, 2, $this->timezone),
        );
    }

    #[Test]
    public function onlineStartWithZeroLeadDaysIsStartOfValidFrom(): void
    {
        $validFrom = $this->timestamp('2026-10-28 12:00');

        self::assertSame(
            $this->timestamp('2026-10-28 00:00'),
            ValidityCalculator::onlineStart($validFrom, 0, $this->timezone),
        );
    }

    #[Test]
    public function defaultValidUntilIsThirteenDaysLater(): void
    {
        $validFrom = $this->timestamp('2026-10-28 00:00');

        self::assertSame(
            $this->timestamp('2026-11-10 00:00'),
            ValidityCalculator::defaultValidUntil($validFrom, $this->timezone),
        );
    }

    #[Test]
    public function weekLabelForStandardTwoWeekPeriod(): void
    {
        self::assertSame('44_45', $this->label('2026-10-28', '2026-11-10'));
    }

    #[Test]
    public function weekLabelMatchesLegacyExample(): void
    {
        self::assertSame('07_08', $this->label('2026-02-11', '2026-02-24'));
    }

    #[Test]
    public function weekLabelForShortPeriodIsSingleWeek(): void
    {
        self::assertSame('44', $this->label('2026-10-28', '2026-11-01'));
    }

    #[Test]
    public function weekLabelWithoutValidUntilIsSingleWeek(): void
    {
        self::assertSame('44', $this->label('2026-10-28', null));
    }

    #[Test]
    public function weekLabelAcrossYearEnd(): void
    {
        self::assertSame('51_52', $this->label('2027-12-22', '2028-01-04'));
        self::assertSame('52_01', $this->label('2027-12-29', '2028-01-11'));
    }

    #[Test]
    public function weekLabelIn53WeekYear(): void
    {
        self::assertSame('51_53', $this->label('2026-12-16', '2027-01-05'));
    }

    private function timestamp(string $dateTime): int
    {
        return (new DateTimeImmutable($dateTime, $this->timezone))->getTimestamp();
    }

    private function label(string $validFrom, ?string $validUntil): string
    {
        return ValidityCalculator::weekLabel(
            new DateTimeImmutable($validFrom, $this->timezone),
            $validUntil === null ? null : new DateTimeImmutable($validUntil, $this->timezone),
        );
    }
}
