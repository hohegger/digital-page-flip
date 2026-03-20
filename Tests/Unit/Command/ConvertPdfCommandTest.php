<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Command;

use Kit\DigitalPageFlip\Command\ConvertPdfCommand;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ConvertPdfCommandTest extends TestCase
{
    private MockObject&FlipbookRepository $flipbookRepositoryMock;
    private MockObject&PdfConversionService $conversionServiceMock;
    private MockObject&PersistenceManager $persistenceManagerMock;
    private ConvertPdfCommand $command;

    protected function setUp(): void
    {
        $this->flipbookRepositoryMock = $this->createMock(FlipbookRepository::class);
        $this->conversionServiceMock = $this->createMock(PdfConversionService::class);
        $this->persistenceManagerMock = $this->createMock(PersistenceManager::class);

        $this->command = new ConvertPdfCommand(
            $this->flipbookRepositoryMock,
            $this->conversionServiceMock,
            $this->persistenceManagerMock,
        );
    }

    // ---------------------------------------------------------------
    // execute() mit UID
    // ---------------------------------------------------------------

    #[Test]
    public function executeWithUidConvertsSpecificFlipbook(): void
    {
        $flipbook = $this->createFlipbookWithUid(42, 'Testkatalog');

        $this->flipbookRepositoryMock
            ->method('findByUid')
            ->with(42)
            ->willReturn($flipbook);

        $this->conversionServiceMock
            ->expects(self::once())
            ->method('convert')
            ->with($flipbook);

        $this->persistenceManagerMock
            ->expects(self::once())
            ->method('persistAll');

        $tester = new CommandTester($this->command);
        $tester->execute(['uid' => '42']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Conversion completed', $tester->getDisplay());
    }

    #[Test]
    public function executeWithUidReturnsFailureWhenFlipbookNotFound(): void
    {
        $this->flipbookRepositoryMock
            ->method('findByUid')
            ->with(99)
            ->willReturn(null);

        $this->conversionServiceMock
            ->expects(self::never())
            ->method('convert');

        $tester = new CommandTester($this->command);
        $tester->execute(['uid' => '99']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('not found', $tester->getDisplay());
    }

    #[Test]
    public function executeWithUidReturnsFailureOnConversionError(): void
    {
        $flipbook = $this->createFlipbookWithUid(42, 'Fehler-Katalog');

        $this->flipbookRepositoryMock
            ->method('findByUid')
            ->with(42)
            ->willReturn($flipbook);

        $this->conversionServiceMock
            ->method('convert')
            ->willThrowException(new RuntimeException('Ghostscript failed'));

        $tester = new CommandTester($this->command);
        $tester->execute(['uid' => '42']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Ghostscript failed', $tester->getDisplay());
    }

    // ---------------------------------------------------------------
    // execute() ohne UID — alle Pending
    // ---------------------------------------------------------------

    #[Test]
    public function executeWithoutUidReturnsSuccessWhenNoPending(): void
    {
        $emptyResult = $this->createMock(QueryResultInterface::class);
        $emptyResult->method('count')->willReturn(0);

        $this->flipbookRepositoryMock
            ->method('findPending')
            ->willReturn($emptyResult);

        $this->conversionServiceMock
            ->expects(self::never())
            ->method('convert');

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No pending', $tester->getDisplay());
    }

    #[Test]
    public function executeWithoutUidConvertsAllPending(): void
    {
        $flipbook = $this->createFlipbookWithUid(1, 'Katalog A');

        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('count')->willReturn(1);

        // Iterator-Verhalten für foreach
        $queryResult->method('rewind');
        $queryResult->method('valid')->willReturnOnConsecutiveCalls(true, false);
        $queryResult->method('current')->willReturn($flipbook);
        $queryResult->method('next');

        $this->flipbookRepositoryMock
            ->method('findPending')
            ->willReturn($queryResult);

        $this->conversionServiceMock
            ->expects(self::once())
            ->method('convert')
            ->with($flipbook);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('converted successfully', $tester->getDisplay());
    }

    #[Test]
    public function executeWithoutUidReportsPartialFailures(): void
    {
        $flipbook1 = $this->createFlipbookWithUid(1, 'OK-Katalog');
        $flipbook2 = $this->createFlipbookWithUid(2, 'Fehler-Katalog');

        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('count')->willReturn(2);

        // Iterator: zwei Elemente
        $queryResult->method('rewind');
        $queryResult->method('valid')->willReturnOnConsecutiveCalls(true, true, false);
        $queryResult->method('current')->willReturnOnConsecutiveCalls($flipbook1, $flipbook2);
        $queryResult->method('next');

        $this->flipbookRepositoryMock
            ->method('findPending')
            ->willReturn($queryResult);

        $callCount = 0;
        $this->conversionServiceMock
            ->method('convert')
            ->willReturnCallback(function () use (&$callCount): void {
                $callCount++;
                if ($callCount === 2) {
                    throw new RuntimeException('Conversion failed');
                }
            });

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('1 of 2', $tester->getDisplay());
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function createFlipbookWithUid(int $uid, string $title): Flipbook
    {
        $flipbook = new Flipbook();
        $flipbook->setTitle($title);

        $property = new ReflectionProperty($flipbook, 'uid');
        $property->setValue($flipbook, $uid);

        return $flipbook;
    }
}
