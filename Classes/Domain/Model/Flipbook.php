<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Model;

use DateTime;
use Kit\DigitalPageFlip\Domain\Validity\ValidityCalculator;
use TYPO3\CMS\Extbase\Attribute\ORM\Cascade;
use TYPO3\CMS\Extbase\Attribute\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class Flipbook extends AbstractEntity
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ERROR = 3;

    protected string $title = '';

    protected string $description = '';

    protected ?FileReference $pdfFile = null;

    /**
     * @var ObjectStorage<Page>
     */
    #[Lazy]
    #[Cascade(value: 'remove')]
    protected ObjectStorage $pages;

    protected int $pageCount = 0;

    protected int $conversionStatus = self::STATUS_PENDING;

    protected ?DateTime $validFrom = null;

    protected ?DateTime $validUntil = null;

    protected int $leadDays = 2;

    protected string $weekLabel = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->pages = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPdfFile(): ?FileReference
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?FileReference $pdfFile): void
    {
        $this->pdfFile = $pdfFile;
    }

    /**
     * @return ObjectStorage<Page>
     */
    public function getPages(): ObjectStorage
    {
        return $this->pages;
    }

    /**
     * @param ObjectStorage<Page> $pages
     */
    public function setPages(ObjectStorage $pages): void
    {
        $this->pages = $pages;
    }

    public function addPage(Page $page): void
    {
        $this->pages->attach($page);
    }

    public function removePage(Page $page): void
    {
        $this->pages->detach($page);
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function setPageCount(int $pageCount): void
    {
        $this->pageCount = $pageCount;
    }

    public function getConversionStatus(): int
    {
        return $this->conversionStatus;
    }

    public function setConversionStatus(int $conversionStatus): void
    {
        $this->conversionStatus = $conversionStatus;
    }

    public function getValidFrom(): ?DateTime
    {
        return $this->validFrom;
    }

    public function setValidFrom(?DateTime $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidUntil(): ?DateTime
    {
        return $this->validUntil;
    }

    public function setValidUntil(?DateTime $validUntil): void
    {
        $this->validUntil = $validUntil;
    }

    public function getLeadDays(): int
    {
        return $this->leadDays;
    }

    public function setLeadDays(int $leadDays): void
    {
        $this->leadDays = $leadDays;
    }

    public function getWeekLabel(): string
    {
        return $this->weekLabel;
    }

    public function setWeekLabel(string $weekLabel): void
    {
        $this->weekLabel = $weekLabel;
    }

    /**
     * Manual week label if set, otherwise calculated from the validity period ('' without validFrom).
     */
    public function getEffectiveWeekLabel(): string
    {
        if ($this->weekLabel !== '') {
            return $this->weekLabel;
        }
        if ($this->validFrom === null) {
            return '';
        }

        return ValidityCalculator::weekLabel($this->validFrom, $this->validUntil);
    }
}
