<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Current;

use DateTimeImmutable;
use DateTimeZone;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Validity\ValidityCalculator;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Read model of the flipbook that is (or will be) published automatically.
 */
final readonly class CurrentFlipbook
{
    public function __construct(
        private int $uid,
        private int $pid,
        private string $title,
        private DateTimeImmutable $validFrom,
        private ?DateTimeImmutable $validUntil,
        private DateTimeImmutable $onlineStart,
        private string $manualWeekLabel,
        private ?FileReference $pdf,
        private ?FileReference $cover,
        private bool $converted,
    ) {}

    /**
     * Builds the read model from a raw flipbook row. A missing starttime falls back to the calculated online start.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(
        array $row,
        ?FileReference $pdf,
        ?FileReference $cover,
        DateTimeZone $timezone,
    ): self {
        $validFrom = (int) ($row['valid_from'] ?? 0);
        $validUntil = (int) ($row['valid_until'] ?? 0);
        $starttime = (int) ($row['starttime'] ?? 0);
        if ($starttime <= 0) {
            $starttime = ValidityCalculator::onlineStart($validFrom, (int) ($row['lead_days'] ?? 0), $timezone);
        }

        return new self(
            (int) ($row['uid'] ?? 0),
            (int) ($row['pid'] ?? 0),
            (string) ($row['title'] ?? ''),
            self::toDate($validFrom, $timezone),
            $validUntil > 0 ? self::toDate($validUntil, $timezone) : null,
            self::toDate($starttime, $timezone),
            (string) ($row['week_label'] ?? ''),
            $pdf,
            $cover,
            (int) ($row['conversion_status'] ?? -1) === Flipbook::STATUS_COMPLETED,
        );
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getValidFrom(): DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function getOnlineStart(): DateTimeImmutable
    {
        return $this->onlineStart;
    }

    /**
     * Effective calendar week label: the manual value wins, otherwise it is derived from the validity period.
     */
    public function getWeekLabel(): string
    {
        if ($this->manualWeekLabel !== '') {
            return $this->manualWeekLabel;
        }

        return ValidityCalculator::weekLabel($this->validFrom, $this->validUntil);
    }

    public function getPdf(): ?FileReference
    {
        return $this->pdf;
    }

    /**
     * Image of the page with the lowest page number.
     */
    public function getCover(): ?FileReference
    {
        return $this->cover;
    }

    public function isConverted(): bool
    {
        return $this->converted;
    }

    private static function toDate(int $timestamp, DateTimeZone $timezone): DateTimeImmutable
    {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone);
    }
}
