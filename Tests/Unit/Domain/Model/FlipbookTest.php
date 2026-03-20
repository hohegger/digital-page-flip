<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Domain\Model;

use DateTime;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class FlipbookTest extends TestCase
{
    private Flipbook $subject;

    protected function setUp(): void
    {
        $this->subject = new Flipbook();
    }

    #[Test]
    public function titleIsEmptyByDefault(): void
    {
        self::assertSame('', $this->subject->getTitle());
    }

    #[Test]
    public function titleCanBeSet(): void
    {
        $this->subject->setTitle('Frühjahrskatalog 2026');
        self::assertSame('Frühjahrskatalog 2026', $this->subject->getTitle());
    }

    #[Test]
    public function descriptionIsEmptyByDefault(): void
    {
        self::assertSame('', $this->subject->getDescription());
    }

    #[Test]
    public function descriptionCanBeSet(): void
    {
        $this->subject->setDescription('Unser neuer Katalog');
        self::assertSame('Unser neuer Katalog', $this->subject->getDescription());
    }

    #[Test]
    public function pdfFileIsNullByDefault(): void
    {
        self::assertNull($this->subject->getPdfFile());
    }

    #[Test]
    public function pdfFileCanBeSet(): void
    {
        $fileReference = $this->createMock(FileReference::class);
        $this->subject->setPdfFile($fileReference);
        self::assertSame($fileReference, $this->subject->getPdfFile());
    }

    #[Test]
    public function pdfFileCanBeSetToNull(): void
    {
        $this->subject->setPdfFile($this->createMock(FileReference::class));
        $this->subject->setPdfFile(null);
        self::assertNull($this->subject->getPdfFile());
    }

    #[Test]
    public function pagesIsEmptyObjectStorageByDefault(): void
    {
        $pages = $this->subject->getPages();
        self::assertInstanceOf(ObjectStorage::class, $pages);
        self::assertCount(0, $pages);
    }

    #[Test]
    public function pageCanBeAdded(): void
    {
        $page = new Page();
        $this->subject->addPage($page);
        self::assertCount(1, $this->subject->getPages());
        self::assertTrue($this->subject->getPages()->contains($page));
    }

    #[Test]
    public function pageCanBeRemoved(): void
    {
        $page = new Page();
        $this->subject->addPage($page);
        $this->subject->removePage($page);
        self::assertCount(0, $this->subject->getPages());
    }

    #[Test]
    public function multiplePagesCanBeAdded(): void
    {
        $this->subject->addPage(new Page());
        $this->subject->addPage(new Page());
        $this->subject->addPage(new Page());
        self::assertCount(3, $this->subject->getPages());
    }

    #[Test]
    public function pagesCanBeReplaced(): void
    {
        $pages = new ObjectStorage();
        $pages->attach(new Page());
        $pages->attach(new Page());
        $this->subject->setPages($pages);
        self::assertCount(2, $this->subject->getPages());
    }

    #[Test]
    public function pageCountIsZeroByDefault(): void
    {
        self::assertSame(0, $this->subject->getPageCount());
    }

    #[Test]
    public function pageCountCanBeSet(): void
    {
        $this->subject->setPageCount(12);
        self::assertSame(12, $this->subject->getPageCount());
    }

    #[Test]
    public function conversionStatusIsPendingByDefault(): void
    {
        self::assertSame(Flipbook::STATUS_PENDING, $this->subject->getConversionStatus());
    }

    #[Test]
    public function conversionStatusCanBeSet(): void
    {
        $this->subject->setConversionStatus(Flipbook::STATUS_COMPLETED);
        self::assertSame(Flipbook::STATUS_COMPLETED, $this->subject->getConversionStatus());
    }

    #[Test]
    public function statusConstantsHaveExpectedValues(): void
    {
        self::assertSame(0, Flipbook::STATUS_PENDING);
        self::assertSame(1, Flipbook::STATUS_PROCESSING);
        self::assertSame(2, Flipbook::STATUS_COMPLETED);
        self::assertSame(3, Flipbook::STATUS_ERROR);
    }

    #[Test]
    public function publishDateIsNullByDefault(): void
    {
        self::assertNull($this->subject->getPublishDate());
    }

    #[Test]
    public function publishDateCanBeSet(): void
    {
        $date = new DateTime('2026-01-15');
        $this->subject->setPublishDate($date);
        self::assertSame($date, $this->subject->getPublishDate());
    }

    #[Test]
    public function publishDateCanBeSetToNull(): void
    {
        $this->subject->setPublishDate(new DateTime());
        $this->subject->setPublishDate(null);
        self::assertNull($this->subject->getPublishDate());
    }

    #[Test]
    public function initializeObjectCreatesEmptyObjectStorage(): void
    {
        $this->subject->addPage(new Page());
        self::assertCount(1, $this->subject->getPages());

        $this->subject->initializeObject();
        self::assertCount(0, $this->subject->getPages());
    }
}
