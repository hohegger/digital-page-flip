<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Domain\Model;

use Kit\DigitalPageFlip\Domain\Model\Page;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

final class PageTest extends TestCase
{
    private Page $subject;

    protected function setUp(): void
    {
        $this->subject = new Page();
    }

    #[Test]
    public function pageNumberIsZeroByDefault(): void
    {
        self::assertSame(0, $this->subject->getPageNumber());
    }

    #[Test]
    public function pageNumberCanBeSet(): void
    {
        $this->subject->setPageNumber(5);
        self::assertSame(5, $this->subject->getPageNumber());
    }

    #[Test]
    public function imageIsNullByDefault(): void
    {
        self::assertNull($this->subject->getImage());
    }

    #[Test]
    public function imageCanBeSet(): void
    {
        $fileReference = $this->createMock(FileReference::class);
        $this->subject->setImage($fileReference);
        self::assertSame($fileReference, $this->subject->getImage());
    }

    #[Test]
    public function imageCanBeSetToNull(): void
    {
        $this->subject->setImage($this->createMock(FileReference::class));
        $this->subject->setImage(null);
        self::assertNull($this->subject->getImage());
    }

    #[Test]
    public function imageFallbackIsNullByDefault(): void
    {
        self::assertNull($this->subject->getImageFallback());
    }

    #[Test]
    public function imageFallbackCanBeSet(): void
    {
        $fileReference = $this->createMock(FileReference::class);
        $this->subject->setImageFallback($fileReference);
        self::assertSame($fileReference, $this->subject->getImageFallback());
    }

    #[Test]
    public function imageFallbackCanBeSetToNull(): void
    {
        $this->subject->setImageFallback($this->createMock(FileReference::class));
        $this->subject->setImageFallback(null);
        self::assertNull($this->subject->getImageFallback());
    }

    #[Test]
    public function sortingIsZeroByDefault(): void
    {
        self::assertSame(0, $this->subject->getSorting());
    }

    #[Test]
    public function sortingCanBeSet(): void
    {
        $this->subject->setSorting(3);
        self::assertSame(3, $this->subject->getSorting());
    }
}
