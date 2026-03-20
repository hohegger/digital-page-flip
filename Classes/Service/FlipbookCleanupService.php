<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Service;

use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

final readonly class FlipbookCleanupService
{
    private const PAGE_TABLE = 'tx_digitalpageflip_domain_model_page';
    private const FLIPBOOK_TABLE = 'tx_digitalpageflip_domain_model_flipbook';
    private const FILE_REF_TABLE = 'sys_file_reference';
    private const FILE_TABLE = 'sys_file';
    private const TARGET_FOLDER_PREFIX = 'user_upload/tx_digitalpageflip/flipbook_';

    public function __construct(
        private ConnectionPool $connectionPool,
        private StorageRepository $storageRepository,
        private ResourceFactory $resourceFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * Removes all pages, file references, FAL files and the folder for a flipbook.
     * Used before re-conversion to start clean.
     */
    public function cleanupForReconversion(int $flipbookUid): void
    {
        $this->cleanupFlipbookPages($flipbookUid);
        $this->deleteFlipbookFolder($flipbookUid);

        $this->logger->info('Cleanup for re-conversion completed.', [
            'flipbookUid' => $flipbookUid,
        ]);
    }

    /**
     * Full cleanup when a flipbook is deleted.
     * Removes pages, file references, FAL files, folder, and the PDF file reference.
     */
    public function cleanupForDeletion(int $flipbookUid): void
    {
        $this->cleanupFlipbookPages($flipbookUid);
        $this->deleteFlipbookFolder($flipbookUid);
        $this->deletePdfFileReference($flipbookUid);

        $this->logger->info('Cleanup for deletion completed.', [
            'flipbookUid' => $flipbookUid,
        ]);
    }

    /**
     * Finds and optionally removes orphaned FAL data across all flipbooks.
     *
     * @return array{references: int, files: int, folders: int}
     */
    public function collectOrphans(bool $dryRun): array
    {
        $result = ['references' => 0, 'files' => 0, 'folders' => 0];

        $result['references'] = $this->cleanupOrphanedReferences($dryRun);
        $result['files'] = $this->cleanupOrphanedFiles($dryRun);
        $result['folders'] = $this->cleanupOrphanedFolders($dryRun);

        return $result;
    }

    /**
     * Deletes all page records and their associated FAL references and files for a flipbook.
     */
    public function cleanupFlipbookPages(int $flipbookUid): void
    {
        $pageUids = $this->getPageUids($flipbookUid);

        if ($pageUids === []) {
            return;
        }

        $fileUids = $this->getFileUidsForPages($pageUids);

        // Delete sys_file_reference records for these pages
        $this->hardDeleteByField(
            self::FILE_REF_TABLE,
            'uid_foreign',
            $pageUids,
            'tablenames',
            'tx_digitalpageflip_domain_model_page',
        );

        // Delete FAL files via storage API
        $this->deleteFilesFromStorage($fileUids);

        // Hard-delete page records (including already soft-deleted ones)
        $this->hardDeleteByField(self::PAGE_TABLE, 'flipbook', [$flipbookUid]);

        $this->logger->info('Cleaned up flipbook pages.', [
            'flipbookUid' => $flipbookUid,
            'pagesDeleted' => count($pageUids),
            'filesDeleted' => count($fileUids),
        ]);
    }

    /**
     * Deletes the flipbook's image folder from FAL storage.
     */
    public function deleteFlipbookFolder(int $flipbookUid): void
    {
        $storage = $this->getDefaultStorage();
        if ($storage === null) {
            return;
        }

        $folderPath = self::TARGET_FOLDER_PREFIX . $flipbookUid . '/';

        if (!$storage->hasFolder($folderPath)) {
            return;
        }

        $folder = $storage->getFolder($folderPath);
        $storage->deleteFolder($folder, true);

        $this->logger->info('Deleted flipbook folder.', [
            'flipbookUid' => $flipbookUid,
            'folder' => $folderPath,
        ]);
    }

    /**
     * Removes the PDF sys_file_reference pointing from the flipbook record.
     * The PDF file itself is kept (may be referenced elsewhere).
     */
    private function deletePdfFileReference(int $flipbookUid): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::FILE_REF_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->delete(self::FILE_REF_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($flipbookUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter(self::FLIPBOOK_TABLE),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('pdf_file'),
                ),
            )
            ->executeStatement();
    }

    /**
     * Finds sys_file_reference records pointing to deleted or non-existent page records.
     */
    private function cleanupOrphanedReferences(bool $dryRun): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::FILE_REF_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('r.uid')
            ->from(self::FILE_REF_TABLE, 'r')
            ->leftJoin(
                'r',
                self::PAGE_TABLE,
                'p',
                $queryBuilder->expr()->eq('p.uid', $queryBuilder->quoteIdentifier('r.uid_foreign')),
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'r.tablenames',
                    $queryBuilder->createNamedParameter('tx_digitalpageflip_domain_model_page'),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('p.uid'),
                    $queryBuilder->expr()->eq('p.deleted', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                ),
            )
            ->executeQuery();

        $orphanedUids = array_column($result->fetchAllAssociative(), 'uid');
        $count = count($orphanedUids);

        if ($count > 0 && !$dryRun) {
            $this->hardDeleteByUid(self::FILE_REF_TABLE, $orphanedUids);
            $this->logger->info('Deleted orphaned sys_file_reference records.', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Finds sys_file records in flipbook folders that have no active sys_file_reference.
     */
    private function cleanupOrphanedFiles(bool $dryRun): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::FILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('f.uid', 'f.identifier')
            ->from(self::FILE_TABLE, 'f')
            ->leftJoin(
                'f',
                self::FILE_REF_TABLE,
                'r',
                (string) $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('r.uid_local', $queryBuilder->quoteIdentifier('f.uid')),
                    $queryBuilder->expr()->eq('r.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                ),
            )
            ->where(
                $queryBuilder->expr()->like(
                    'f.identifier',
                    $queryBuilder->createNamedParameter('/user_upload/tx_digitalpageflip/flipbook_%'),
                ),
                $queryBuilder->expr()->isNull('r.uid'),
            )
            ->executeQuery();

        $orphanedFiles = $result->fetchAllAssociative();
        $count = count($orphanedFiles);

        if ($count > 0 && !$dryRun) {
            $storage = $this->getDefaultStorage();
            if ($storage !== null) {
                foreach ($orphanedFiles as $fileRecord) {
                    try {
                        $file = $storage->getFile((string) $fileRecord['identifier']);
                        $file->delete();
                    } catch (Throwable $e) {
                        $this->logger->warning('Could not delete orphaned file.', [
                            'identifier' => $fileRecord['identifier'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            $this->logger->info('Deleted orphaned FAL files.', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Finds flipbook folders that belong to non-existent or deleted flipbooks.
     */
    private function cleanupOrphanedFolders(bool $dryRun): int
    {
        $storage = $this->getDefaultStorage();
        if ($storage === null) {
            return 0;
        }

        $baseFolderPath = 'user_upload/tx_digitalpageflip/';
        if (!$storage->hasFolder($baseFolderPath)) {
            return 0;
        }

        $baseFolder = $storage->getFolder($baseFolderPath);
        $subFolders = $baseFolder->getSubfolders();
        $count = 0;

        foreach ($subFolders as $subFolder) {
            $folderName = $subFolder->getName();
            if (!str_starts_with($folderName, 'flipbook_')) {
                continue;
            }

            $flipbookUid = (int) substr($folderName, strlen('flipbook_'));
            if ($flipbookUid <= 0) {
                continue;
            }

            if (!$this->flipbookExists($flipbookUid)) {
                $count++;
                if (!$dryRun) {
                    $storage->deleteFolder($subFolder, true);
                    $this->logger->info('Deleted orphaned folder.', ['folder' => $folderName]);
                }
            }
        }

        return $count;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * @return list<int>
     */
    private function getPageUids(int $flipbookUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PAGE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid')
            ->from(self::PAGE_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'flipbook',
                    $queryBuilder->createNamedParameter($flipbookUid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery();

        return array_map(intval(...), array_column($result->fetchAllAssociative(), 'uid'));
    }

    /**
     * @param list<int> $pageUids
     * @return list<int>
     */
    private function getFileUidsForPages(array $pageUids): array
    {
        if ($pageUids === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::FILE_REF_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid_local')
            ->from(self::FILE_REF_TABLE)
            ->where(
                $queryBuilder->expr()->in(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($pageUids, Connection::PARAM_INT_ARRAY),
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_digitalpageflip_domain_model_page'),
                ),
            )
            ->executeQuery();

        return array_map(intval(...), array_column($result->fetchAllAssociative(), 'uid_local'));
    }

    /**
     * @param list<int> $fileUids
     */
    private function deleteFilesFromStorage(array $fileUids): void
    {
        if ($fileUids === []) {
            return;
        }

        foreach ($fileUids as $fileUid) {
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
                $file->delete();
            } catch (Throwable $e) {
                $this->logger->warning('Could not delete FAL file.', [
                    'fileUid' => $fileUid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param list<int> $values
     */
    private function hardDeleteByField(string $table, string $field, array $values, string $additionalField = '', string $additionalValue = ''): void
    {
        if ($values === []) {
            return;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->delete($table)
            ->where(
                $queryBuilder->expr()->in(
                    $field,
                    $queryBuilder->createNamedParameter($values, Connection::PARAM_INT_ARRAY),
                ),
            );

        if ($additionalField !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $additionalField,
                    $queryBuilder->createNamedParameter($additionalValue),
                ),
            );
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @param list<int> $uids
     */
    private function hardDeleteByUid(string $table, array $uids): void
    {
        $this->hardDeleteByField($table, 'uid', $uids);
    }

    private function flipbookExists(int $uid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::FLIPBOOK_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $count = (int) $queryBuilder
            ->count('uid')
            ->from(self::FLIPBOOK_TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();

        return $count > 0;
    }

    private function getDefaultStorage(): ?ResourceStorage
    {
        return $this->storageRepository->getDefaultStorage();
    }
}
