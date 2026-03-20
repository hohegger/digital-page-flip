<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use Kit\DigitalPageFlip\ViewHelpers\FlipbookDataViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

final class FlipbookDataViewHelperTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            false,
            '/var/www/html',
            '/var/www/html/public',
            '/var/www/html/var',
            '/var/www/html/config',
            '/var/www/html/public/index.php',
            'UNIX',
        );
    }

    #[Test]
    public function renderStaticReturnsJsonArrayOfImageUrls(): void
    {
        $flipbook = new Flipbook();
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1));
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_002.webp', 2));

        $result = $this->invokeRenderStatic($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(2, $decoded);
        self::assertStringContainsString('page_001.webp', $decoded[0]);
        self::assertStringContainsString('page_002.webp', $decoded[1]);
    }

    #[Test]
    public function renderStaticReturnsEmptyJsonArrayForFlipbookWithoutPages(): void
    {
        $flipbook = new Flipbook();

        $result = $this->invokeRenderStatic($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(0, $decoded);
    }

    #[Test]
    public function renderStaticSkipsPagesWithoutImage(): void
    {
        $flipbook = new Flipbook();

        $pageWithImage = $this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1);
        $pageWithoutImage = new Page();
        $pageWithoutImage->setPageNumber(2);

        $flipbook->addPage($pageWithImage);
        $flipbook->addPage($pageWithoutImage);

        $result = $this->invokeRenderStatic($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(1, $decoded);
    }

    #[Test]
    public function renderStaticSkipsPagesWithNullOriginalResource(): void
    {
        $flipbook = new Flipbook();

        $extbaseRef = $this->createMock(FileReference::class);
        $extbaseRef->method('getOriginalResource')->willReturn(null);

        $page = new Page();
        $page->setPageNumber(1);
        $page->setImage($extbaseRef);

        $flipbook->addPage($page);

        $result = $this->invokeRenderStatic($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(0, $decoded);
    }

    #[Test]
    public function renderStaticOutputIsHtmlspecialcharsEncoded(): void
    {
        $flipbook = new Flipbook();
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1));

        $result = $this->invokeRenderStatic($flipbook);

        // JSON enthält Anführungszeichen, die escaped sein müssen
        self::assertStringNotContainsString('"', $result);
        self::assertStringContainsString('&quot;', $result);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function invokeRenderStatic(Flipbook $flipbook): string
    {
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderChildrenClosure = static fn(): string => '';

        return FlipbookDataViewHelper::renderStatic(
            ['flipbook' => $flipbook],
            $renderChildrenClosure,
            $renderingContext,
        );
    }

    private function createPageWithImageUrl(string $publicUrl, int $pageNumber): Page
    {
        $coreRef = $this->createMock(CoreFileReference::class);
        $coreRef->method('getPublicUrl')->willReturn($publicUrl);

        $extbaseRef = $this->createMock(FileReference::class);
        $extbaseRef->method('getOriginalResource')->willReturn($coreRef);

        $page = new Page();
        $page->setPageNumber($pageNumber);
        $page->setSorting($pageNumber);
        $page->setImage($extbaseRef);

        return $page;
    }
}
