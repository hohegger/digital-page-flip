<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Command;

use Kit\DigitalPageFlip\Command\CleanupCommand;
use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupCommandTest extends TestCase
{
    private MockObject&FlipbookCleanupService $cleanupServiceMock;
    private CleanupCommand $command;

    protected function setUp(): void
    {
        $this->cleanupServiceMock = $this->createMock(FlipbookCleanupService::class);
        $this->command = new CleanupCommand($this->cleanupServiceMock);
    }

    #[Test]
    public function executeWithDryRunCallsCollectOrphansInDryRunMode(): void
    {
        $this->cleanupServiceMock
            ->expects(self::once())
            ->method('collectOrphans')
            ->with(true)
            ->willReturn(['references' => 0, 'files' => 0, 'folders' => 0]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Dry-run', $tester->getDisplay());
    }

    #[Test]
    public function executeWithoutDryRunCallsCollectOrphansWithDelete(): void
    {
        $this->cleanupServiceMock
            ->expects(self::once())
            ->method('collectOrphans')
            ->with(false)
            ->willReturn(['references' => 0, 'files' => 0, 'folders' => 0]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    #[Test]
    public function executeReportsNoOrphansFound(): void
    {
        $this->cleanupServiceMock
            ->method('collectOrphans')
            ->willReturn(['references' => 0, 'files' => 0, 'folders' => 0]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertStringContainsString('No orphaned data found', $tester->getDisplay());
    }

    #[Test]
    public function executeReportsOrphanCounts(): void
    {
        $this->cleanupServiceMock
            ->method('collectOrphans')
            ->willReturn(['references' => 5, 'files' => 3, 'folders' => 1]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('5 orphaned sys_file_reference', $display);
        self::assertStringContainsString('3 orphaned FAL file', $display);
        self::assertStringContainsString('1 orphaned folder', $display);
        self::assertStringContainsString('9 item(s) removed', $display);
    }

    #[Test]
    public function executeDryRunShowsWouldBeCleanedMessage(): void
    {
        $this->cleanupServiceMock
            ->method('collectOrphans')
            ->willReturn(['references' => 2, 'files' => 0, 'folders' => 1]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--dry-run' => true]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('3 item(s) would be cleaned up', $display);
        self::assertStringContainsString('Run without --dry-run', $display);
    }

    #[Test]
    public function executeAlwaysReturnsSuccess(): void
    {
        $this->cleanupServiceMock
            ->method('collectOrphans')
            ->willReturn(['references' => 10, 'files' => 5, 'folders' => 2]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }
}
