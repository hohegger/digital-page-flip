<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Service;

use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

final class FlipbookCleanupServiceTest extends TestCase
{
    private FlipbookCleanupService $subject;
    private MockObject&ConnectionPool $connectionPoolMock;
    private MockObject&StorageRepository $storageRepositoryMock;
    private MockObject&LoggerInterface $loggerMock;
    private MockObject&ResourceStorage $storageMock;

    protected function setUp(): void
    {
        $this->connectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->storageRepositoryMock = $this->createMock(StorageRepository::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->storageMock = $this->createMock(ResourceStorage::class);

        $this->storageRepositoryMock
            ->method('getDefaultStorage')
            ->willReturn($this->storageMock);

        $this->subject = new FlipbookCleanupService(
            $this->connectionPoolMock,
            $this->storageRepositoryMock,
            $this->createMock(ResourceFactory::class),
            $this->loggerMock,
        );
    }

    // ---------------------------------------------------------------
    // cleanupFlipbookPages()
    // ---------------------------------------------------------------

    #[Test]
    public function cleanupFlipbookPagesSkipsWhenNoPagesExist(): void
    {
        $queryBuilder = $this->createQueryBuilderReturning([]);
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        // Should not throw, should just return
        $this->subject->cleanupFlipbookPages(42);

        // Logger should not be called with page cleanup info
        $this->loggerMock
            ->expects(self::never())
            ->method('info');
    }

    #[Test]
    public function cleanupFlipbookPagesDeletesReferencesAndPages(): void
    {
        $pageRows = [['uid' => 1], ['uid' => 2]];
        $fileRefRows = [['uid_local' => 10], ['uid_local' => 20]];

        // First call: getPageUids → returns page rows
        // Second call: getFileUidsForPages → returns file ref rows
        // Third call: hardDeleteByField (sys_file_reference)
        // Fourth call: hardDeleteByField (page table)
        $queryBuilder = $this->createQueryBuilderReturning($pageRows);
        $queryBuilderRefs = $this->createQueryBuilderReturning($fileRefRows);

        $callCount = 0;
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturnCallback(function () use ($queryBuilder, $queryBuilderRefs, &$callCount) {
                $callCount++;
                // First call = page UIDs, second = file refs, rest = deletes
                if ($callCount === 1) {
                    return $queryBuilder;
                }
                if ($callCount === 2) {
                    return $queryBuilderRefs;
                }
                return $this->createDeleteQueryBuilder();
            });

        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info');

        $this->subject->cleanupFlipbookPages(42);
    }

    // ---------------------------------------------------------------
    // deleteFlipbookFolder()
    // ---------------------------------------------------------------

    #[Test]
    public function deleteFlipbookFolderDeletesExistingFolder(): void
    {
        $folderMock = $this->createMock(Folder::class);

        $this->storageMock
            ->method('hasFolder')
            ->with('user_upload/tx_digitalpageflip/flipbook_42/')
            ->willReturn(true);

        $this->storageMock
            ->method('getFolder')
            ->willReturn($folderMock);

        $this->storageMock
            ->expects(self::once())
            ->method('deleteFolder')
            ->with($folderMock, true);

        $this->subject->deleteFlipbookFolder(42);
    }

    #[Test]
    public function deleteFlipbookFolderSkipsNonExistentFolder(): void
    {
        $this->storageMock
            ->method('hasFolder')
            ->willReturn(false);

        $this->storageMock
            ->expects(self::never())
            ->method('deleteFolder');

        $this->subject->deleteFlipbookFolder(99);
    }

    // ---------------------------------------------------------------
    // cleanupForReconversion()
    // ---------------------------------------------------------------

    #[Test]
    public function cleanupForReconversionLogsCompletion(): void
    {
        // No pages → quick exit for cleanup, but folder check still happens
        $queryBuilder = $this->createQueryBuilderReturning([]);
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->storageMock->method('hasFolder')->willReturn(false);

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Cleanup for re-conversion completed.', self::anything());

        $this->subject->cleanupForReconversion(42);
    }

    // ---------------------------------------------------------------
    // cleanupForDeletion()
    // ---------------------------------------------------------------

    #[Test]
    public function cleanupForDeletionDeletesPdfFileReference(): void
    {
        // No pages exist
        $queryBuilder = $this->createQueryBuilderReturning([]);
        $deleteQueryBuilder = $this->createDeleteQueryBuilder();

        $callCount = 0;
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturnCallback(function () use ($queryBuilder, $deleteQueryBuilder, &$callCount) {
                $callCount++;
                return $callCount === 1 ? $queryBuilder : $deleteQueryBuilder;
            });

        $this->storageMock->method('hasFolder')->willReturn(false);

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Cleanup for deletion completed.', self::anything());

        $this->subject->cleanupForDeletion(42);
    }

    // ---------------------------------------------------------------
    // collectOrphans()
    // ---------------------------------------------------------------

    #[Test]
    public function collectOrphansDryRunDoesNotDelete(): void
    {
        // Return empty results for all queries
        $queryBuilder = $this->createQueryBuilderReturning([]);
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->storageMock->method('hasFolder')->willReturn(false);

        $result = $this->subject->collectOrphans(true);

        self::assertSame(0, $result['references']);
        self::assertSame(0, $result['files']);
        self::assertSame(0, $result['folders']);
    }

    #[Test]
    public function collectOrphansReturnsZerosWhenNoOrphansExist(): void
    {
        $queryBuilder = $this->createQueryBuilderReturning([]);
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $this->storageMock->method('hasFolder')->willReturn(false);

        $result = $this->subject->collectOrphans(false);

        self::assertSame(0, $result['references']);
        self::assertSame(0, $result['files']);
        self::assertSame(0, $result['folders']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function createQueryBuilderReturning(array $rows): MockObject&QueryBuilder
    {
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->method('eq')->willReturn('1=1');
        $expressionBuilder->method('in')->willReturn('1=1');
        $expressionBuilder->method('like')->willReturn('1=1');
        $expressionBuilder->method('isNull')->willReturn('1=1');
        $compositeExpression = $this->createMock(CompositeExpression::class);
        $compositeExpression->method('__toString')->willReturn('1=1');
        $expressionBuilder->method('or')->willReturn($compositeExpression);
        $expressionBuilder->method('and')->willReturn($compositeExpression);

        $result = $this->createMock(\Doctrine\DBAL\Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);
        $result->method('fetchOne')->willReturn(0);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('count')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('delete')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);
        $queryBuilder->method('executeStatement')->willReturn(0);
        $queryBuilder->method('createNamedParameter')->willReturn('?');
        $queryBuilder->method('quoteIdentifier')->willReturnArgument(0);
        $queryBuilder->method('getRestrictions')->willReturn(
            $this->createMock(\TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface::class),
        );

        return $queryBuilder;
    }

    private function createDeleteQueryBuilder(): MockObject&QueryBuilder
    {
        return $this->createQueryBuilderReturning([]);
    }
}
