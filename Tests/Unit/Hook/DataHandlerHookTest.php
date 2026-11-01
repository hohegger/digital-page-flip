<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Hook;

use DateTimeImmutable;
use DateTimeZone;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Hook\DataHandlerHook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DataHandlerHookTest extends TestCase
{
    // ---------------------------------------------------------------
    // shouldConvert() — decision logic
    // ---------------------------------------------------------------

    #[Test]
    public function processingStatusAlwaysPreventsConversion(): void
    {
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_PROCESSING, false));
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_PROCESSING, true));
    }

    #[Test]
    public function completedStatusWithoutPdfChangeSkipsConversion(): void
    {
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_COMPLETED, false));
    }

    #[Test]
    public function completedStatusWithPdfChangeAllowsReconversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_COMPLETED, true));
    }

    #[Test]
    public function pendingStatusAllowsConversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_PENDING, false));
    }

    #[Test]
    public function errorStatusAllowsConversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_ERROR, false));
    }

    // ---------------------------------------------------------------
    // PDF change detection via DataHandler datamap
    // ---------------------------------------------------------------

    #[Test]
    public function pdfChangedDetectionViaDatamap(): void
    {
        $datamap = [
            'tx_digitalpageflip_domain_model_flipbook' => [
                42 => ['pdf_file' => 1],
            ],
        ];

        self::assertTrue(isset($datamap['tx_digitalpageflip_domain_model_flipbook'][42]['pdf_file']));
    }

    #[Test]
    public function noPdfChangeDetectionViaDatamap(): void
    {
        $datamap = [
            'tx_digitalpageflip_domain_model_flipbook' => [
                42 => ['title' => 'Updated Title'],
            ],
        ];

        self::assertFalse(isset($datamap['tx_digitalpageflip_domain_model_flipbook'][42]['pdf_file']));
    }

    // ---------------------------------------------------------------
    // deriveSchedule() — online start derivation
    // ---------------------------------------------------------------

    private static function ts(string $date): int
    {
        return (new DateTimeImmutable($date, new DateTimeZone('Europe/Berlin')))->getTimestamp();
    }

    #[Test]
    public function deriveScheduleSetsStarttimeFromValidFromAndLeadDays(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['valid_from' => self::ts('2026-10-28 00:00'), 'lead_days' => 2],
            [],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertSame(self::ts('2026-10-26 00:00'), $result['starttime']);
        self::assertSame(0, $result['endtime']);
    }

    #[Test]
    public function deriveScheduleFillsMissingValidUntil(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['valid_from' => self::ts('2026-10-28 00:00')],
            [],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertSame(self::ts('2026-11-10 00:00'), $result['valid_until']);
    }

    #[Test]
    public function deriveScheduleKeepsGivenValidUntil(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['valid_from' => self::ts('2026-10-28 00:00'), 'valid_until' => self::ts('2026-11-05 00:00')],
            [],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertArrayNotHasKey('valid_until', $result);
    }

    #[Test]
    public function deriveScheduleKeepsValidUntilFromCurrentRecord(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['lead_days' => 1],
            ['valid_from' => self::ts('2026-10-28 00:00'), 'valid_until' => self::ts('2026-11-05 00:00'), 'lead_days' => 2],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertArrayNotHasKey('valid_until', $result);
        self::assertSame(self::ts('2026-10-27 00:00'), $result['starttime']);
    }

    #[Test]
    public function deriveScheduleRecalculatesOnChangedLeadDays(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['lead_days' => '3'],
            ['valid_from' => self::ts('2026-10-28 00:00'), 'lead_days' => 2],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertSame(self::ts('2026-10-25 00:00'), $result['starttime']);
    }

    #[Test]
    public function deriveScheduleDefaultsLeadDaysToTwo(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['valid_from' => self::ts('2026-10-28 00:00')],
            [],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertSame(self::ts('2026-10-26 00:00'), $result['starttime']);
    }

    #[Test]
    public function deriveScheduleLeavesLegacyRecordsUntouched(): void
    {
        self::assertSame([], DataHandlerHook::deriveSchedule(
            ['title' => 'x', 'valid_from' => 0],
            ['valid_from' => 0],
            new DateTimeZone('Europe/Berlin'),
        ));
    }

    #[Test]
    public function deriveScheduleResetsWhenValidFromIsCleared(): void
    {
        self::assertSame([], DataHandlerHook::deriveSchedule(
            ['valid_from' => ''],
            ['valid_from' => self::ts('2026-10-28 00:00')],
            new DateTimeZone('Europe/Berlin'),
        ));
    }

    #[Test]
    public function deriveScheduleAcceptsIsoDateStrings(): void
    {
        $result = DataHandlerHook::deriveSchedule(
            ['valid_from' => '2026-10-27T23:00:00Z', 'valid_until' => '2026-11-09T23:00:00+00:00'],
            [],
            new DateTimeZone('Europe/Berlin'),
        );

        self::assertSame(self::ts('2026-10-26 00:00'), $result['starttime']);
        self::assertArrayNotHasKey('valid_until', $result);
    }
}
