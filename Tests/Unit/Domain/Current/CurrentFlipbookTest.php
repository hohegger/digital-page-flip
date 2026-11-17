<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Domain\Current;

use DateTimeImmutable;
use DateTimeZone;
use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbook;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileReference;

final class CurrentFlipbookTest extends TestCase
{
    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->timezone = new DateTimeZone('Europe/Berlin');
    }

    #[Test]
    public function gettersReturnRowValues(): void
    {
        $pdf = $this->createStub(FileReference::class);
        $cover = $this->createStub(FileReference::class);

        $subject = CurrentFlipbook::fromRow($this->row(), $pdf, $cover, $this->timezone);

        self::assertSame(12, $subject->getUid());
        self::assertSame(34, $subject->getPid());
        self::assertSame('Angebote KW 44/45', $subject->getTitle());
        self::assertSame($this->timestamp('2026-10-28 00:00'), $subject->getValidFrom()->getTimestamp());
        self::assertSame($this->timestamp('2026-11-10 00:00'), $subject->getValidUntil()?->getTimestamp());
        self::assertSame($this->timestamp('2026-10-26 00:00'), $subject->getOnlineStart()->getTimestamp());
        self::assertSame($pdf, $subject->getPdf());
        self::assertSame($cover, $subject->getCover());
        self::assertTrue($subject->isConverted());
    }

    #[Test]
    public function datesAreExpressedInGivenTimezone(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(), null, null, $this->timezone);

        self::assertSame('2026-10-28 00:00', $subject->getValidFrom()->format('Y-m-d H:i'));
        self::assertSame('Europe/Berlin', $subject->getValidFrom()->getTimezone()->getName());
    }

    #[Test]
    public function fileReferencesAreOptional(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(), null, null, $this->timezone);

        self::assertNull($subject->getPdf());
        self::assertNull($subject->getCover());
    }

    #[Test]
    public function validUntilIsNullWhenEmpty(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(['valid_until' => 0]), null, null, $this->timezone);

        self::assertNull($subject->getValidUntil());
    }

    #[Test]
    public function weekLabelPrefersManualValue(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(['week_label' => '44_46']), null, null, $this->timezone);

        self::assertSame('44_46', $subject->getWeekLabel());
    }

    #[Test]
    public function weekLabelFallsBackToCalculatedLabel(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(), null, null, $this->timezone);

        self::assertSame('44_45', $subject->getWeekLabel());
    }

    #[Test]
    public function weekLabelWithoutValidUntilIsSingleWeek(): void
    {
        $subject = CurrentFlipbook::fromRow($this->row(['valid_until' => 0]), null, null, $this->timezone);

        self::assertSame('44', $subject->getWeekLabel());
    }

    #[Test]
    public function onlineStartIsCalculatedWhenStarttimeIsMissing(): void
    {
        $subject = CurrentFlipbook::fromRow(
            $this->row(['starttime' => 0, 'lead_days' => 3]),
            null,
            null,
            $this->timezone,
        );

        self::assertSame($this->timestamp('2026-10-25 00:00'), $subject->getOnlineStart()->getTimestamp());
    }

    #[Test]
    public function notCompletedFlipbookIsNotConverted(): void
    {
        $subject = CurrentFlipbook::fromRow(
            $this->row(['conversion_status' => Flipbook::STATUS_PROCESSING]),
            null,
            null,
            $this->timezone,
        );

        self::assertFalse($subject->isConverted());
    }

    #[Test]
    public function databaseStringValuesAreAccepted(): void
    {
        $row = array_map(static fn(int|string $value): string => (string) $value, $this->row());

        $subject = CurrentFlipbook::fromRow($row, null, null, $this->timezone);

        self::assertSame(12, $subject->getUid());
        self::assertSame($this->timestamp('2026-10-26 00:00'), $subject->getOnlineStart()->getTimestamp());
        self::assertTrue($subject->isConverted());
    }

    /**
     * @param array<string, int|string> $overrides
     * @return array<string, int|string>
     */
    private function row(array $overrides = []): array
    {
        return array_replace([
            'uid' => 12,
            'pid' => 34,
            'title' => 'Angebote KW 44/45',
            'starttime' => $this->timestamp('2026-10-26 00:00'),
            'valid_from' => $this->timestamp('2026-10-28 00:00'),
            'valid_until' => $this->timestamp('2026-11-10 00:00'),
            'lead_days' => 2,
            'week_label' => '',
            'conversion_status' => Flipbook::STATUS_COMPLETED,
        ], $overrides);
    }

    private function timestamp(string $dateTime): int
    {
        return (new DateTimeImmutable($dateTime, $this->timezone))->getTimestamp();
    }
}
