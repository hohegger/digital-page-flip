<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use Kit\DigitalPageFlip\ViewHelpers\FlipbookDataViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

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
    public function renderReturnsJsonArrayOfImageUrls(): void
    {
        $flipbook = new Flipbook();
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1));
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_002.webp', 2));

        $result = $this->invokeRender($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(2, $decoded);
        self::assertStringContainsString('page_001.webp', $decoded[0]);
        self::assertStringContainsString('page_002.webp', $decoded[1]);
    }

    #[Test]
    public function renderReturnsEmptyJsonArrayForFlipbookWithoutPages(): void
    {
        $flipbook = new Flipbook();

        $result = $this->invokeRender($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(0, $decoded);
    }

    #[Test]
    public function renderSkipsPagesWithoutImage(): void
    {
        $flipbook = new Flipbook();

        $pageWithImage = $this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1);
        $pageWithoutImage = new Page();
        $pageWithoutImage->setPageNumber(2);

        $flipbook->addPage($pageWithImage);
        $flipbook->addPage($pageWithoutImage);

        $result = $this->invokeRender($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(1, $decoded);
    }

    #[Test]
    public function renderSkipsPagesWithMissingOriginalResource(): void
    {
        $flipbook = new Flipbook();

        // TYPO3 v14: getOriginalResource() ist non-nullable typisiert (FileReference).
        // Fehlt die Original-Resource, wirft der Getter einen \TypeError statt null zurückzugeben.
        $extbaseRef = $this->createMock(FileReference::class);
        $extbaseRef->method('getOriginalResource')->willThrowException(
            new TypeError('TYPO3\CMS\Extbase\Domain\Model\FileReference::getOriginalResource(): Return value must be of type TYPO3\CMS\Core\Resource\FileReference, none returned'),
        );

        $page = new Page();
        $page->setPageNumber(1);
        $page->setImage($extbaseRef);

        $flipbook->addPage($page);

        $result = $this->invokeRender($flipbook);
        $decoded = json_decode(html_entity_decode($result, ENT_QUOTES, 'UTF-8'), true);

        self::assertIsArray($decoded);
        self::assertCount(0, $decoded);
    }

    #[Test]
    public function renderOutputIsHtmlspecialcharsEncoded(): void
    {
        $flipbook = new Flipbook();
        $flipbook->addPage($this->createPageWithImageUrl('fileadmin/flipbook/page_001.webp', 1));

        $result = $this->invokeRender($flipbook);

        // JSON enthält Anführungszeichen, die escaped sein müssen
        self::assertStringNotContainsString('"', $result);
        self::assertStringContainsString('&quot;', $result);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function invokeRender(Flipbook $flipbook): string
    {
        $viewHelper = new FlipbookDataViewHelper();
        $viewHelper->setArguments(['flipbook' => $flipbook]);

        return $viewHelper->render();
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
