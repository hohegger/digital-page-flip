<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Service;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class PdfConversionServiceTest extends TestCase
{
    private PdfConversionService $subject;
    private MockObject&FlipbookRepository $flipbookRepositoryMock;
    private MockObject&PersistenceManager $persistenceManagerMock;
    private MockObject&LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->flipbookRepositoryMock = $this->createMock(FlipbookRepository::class);
        $this->persistenceManagerMock = $this->createMock(PersistenceManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->subject = new PdfConversionService(
            $this->createMock(ResourceFactory::class),
            $this->createMock(StorageRepository::class),
            $this->flipbookRepositoryMock,
            $this->persistenceManagerMock,
            $this->createMock(FlipbookCleanupService::class),
            $this->loggerMock,
        );
    }

    // ---------------------------------------------------------------
    // convert() — Fehlerbehandlung
    // ---------------------------------------------------------------

    #[Test]
    public function convertThrowsExceptionWhenNoPdfFileAttached(): void
    {
        $flipbook = new Flipbook();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000001);

        $this->subject->convert($flipbook);
    }

    #[Test]
    public function convertSetsStatusToErrorWhenNoPdfFileAttached(): void
    {
        $flipbook = new Flipbook();

        try {
            $this->subject->convert($flipbook);
            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            self::assertSame(Flipbook::STATUS_ERROR, $flipbook->getConversionStatus());
        }
    }

    #[Test]
    public function convertPersistsErrorStatusOnFailure(): void
    {
        $flipbook = new Flipbook();

        $this->flipbookRepositoryMock
            ->expects(self::once())
            ->method('update')
            ->with($flipbook);

        $this->persistenceManagerMock
            ->expects(self::once())
            ->method('persistAll');

        try {
            $this->subject->convert($flipbook);
        } catch (RuntimeException) {
            // Expected
        }
    }

    #[Test]
    public function convertLogsErrorOnFailure(): void
    {
        $flipbook = new Flipbook();

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'PDF conversion failed.',
                self::callback(static function (array $context): bool {
                    return isset($context['error'], $context['code']);
                }),
            );

        try {
            $this->subject->convert($flipbook);
        } catch (RuntimeException) {
            // Expected
        }
    }

    #[Test]
    public function convertThrowsExceptionOnInvalidMimeType(): void
    {
        $flipbook = new Flipbook();
        $flipbook->setPdfFile($this->createFileReferenceWithMimeType('image/png'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000010);

        $this->subject->convert($flipbook);
    }

    #[Test]
    public function convertSetsStatusToErrorOnInvalidMimeType(): void
    {
        $flipbook = new Flipbook();
        $flipbook->setPdfFile($this->createFileReferenceWithMimeType('text/html'));

        try {
            $this->subject->convert($flipbook);
            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            self::assertSame(Flipbook::STATUS_ERROR, $flipbook->getConversionStatus());
        }
    }

    // ---------------------------------------------------------------
    // validatePdf() — via Reflection
    // ---------------------------------------------------------------

    #[Test]
    public function validatePdfAcceptsApplicationPdf(): void
    {
        $fileReference = $this->createFileReferenceWithMimeType('application/pdf');

        $method = new ReflectionMethod(PdfConversionService::class, 'validatePdf');
        $method->invoke($this->subject, $fileReference);

        // Kein Exception = Test bestanden
        self::assertTrue(true);
    }

    #[Test]
    public function validatePdfThrowsOnImageMimeType(): void
    {
        $fileReference = $this->createFileReferenceWithMimeType('image/jpeg');

        $method = new ReflectionMethod(PdfConversionService::class, 'validatePdf');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000010);
        $method->invoke($this->subject, $fileReference);
    }

    #[Test]
    public function validatePdfThrowsOnTextMimeType(): void
    {
        $fileReference = $this->createFileReferenceWithMimeType('text/plain');

        $method = new ReflectionMethod(PdfConversionService::class, 'validatePdf');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000010);
        $method->invoke($this->subject, $fileReference);
    }

    // ---------------------------------------------------------------
    // resolveGhostscriptPath() — via Reflection
    // ---------------------------------------------------------------

    #[Test]
    public function resolveGhostscriptPathReturnsConfiguredExecutablePath(): void
    {
        $method = new ReflectionMethod(PdfConversionService::class, 'resolveGhostscriptPath');

        // /usr/bin/env existiert auf jedem Unix-System
        $result = $method->invoke($this->subject, ['ghostscriptPath' => '/usr/bin/env']);
        self::assertSame('/usr/bin/env', $result);
    }

    #[Test]
    public function resolveGhostscriptPathFallsBackToDefaultWhenConfiguredPathInvalid(): void
    {
        if (!is_executable('/usr/bin/gs')) {
            self::markTestSkipped('Ghostscript nicht unter /usr/bin/gs installiert.');
        }

        $method = new ReflectionMethod(PdfConversionService::class, 'resolveGhostscriptPath');

        $result = $method->invoke($this->subject, ['ghostscriptPath' => '/nonexistent/gs']);
        self::assertSame('/usr/bin/gs', $result);
    }

    #[Test]
    public function resolveGhostscriptPathThrowsWhenNoBinaryFound(): void
    {
        if (is_executable('/usr/bin/gs')) {
            self::markTestSkipped('Ghostscript ist unter /usr/bin/gs installiert — Fallback greift.');
        }

        $method = new ReflectionMethod(PdfConversionService::class, 'resolveGhostscriptPath');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000050);
        $method->invoke($this->subject, ['ghostscriptPath' => '/nonexistent/binary']);
    }

    #[Test]
    public function resolveGhostscriptPathThrowsOnEmptyStringWithoutDefault(): void
    {
        if (is_executable('/usr/bin/gs')) {
            self::markTestSkipped('Ghostscript ist unter /usr/bin/gs installiert — Fallback greift.');
        }

        $method = new ReflectionMethod(PdfConversionService::class, 'resolveGhostscriptPath');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1710000050);
        $method->invoke($this->subject, ['ghostscriptPath' => '']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function createFileReferenceWithMimeType(string $mimeType): FileReference
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn($mimeType);

        $coreFileReference = $this->createMock(CoreFileReference::class);
        $coreFileReference->method('getOriginalFile')->willReturn($file);

        $extbaseFileReference = $this->createMock(FileReference::class);
        $extbaseFileReference->method('getOriginalResource')->willReturn($coreFileReference);

        return $extbaseFileReference;
    }
}
