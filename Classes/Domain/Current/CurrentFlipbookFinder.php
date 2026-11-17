<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Current;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;

/**
 * Database access for the automatically published flipbook (raw rows and FAL references).
 */
final readonly class CurrentFlipbookFinder
{
    public const TABLE = 'tx_digitalpageflip_domain_model_flipbook';
    private const PAGE_TABLE = 'tx_digitalpageflip_domain_model_page';

    public function __construct(
        private ConnectionPool $connectionPool,
        private FileRepository $fileRepository,
    ) {}

    /**
     * Candidate rows with default restrictions (deleted, hidden, starttime, endtime).
     * With $includeScheduled the starttime restriction is dropped to also see future online starts.
     * $storagePid = 0 searches all pages.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchCandidates(int $storagePid, bool $includeScheduled): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        if ($includeScheduled) {
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
        }

        $queryBuilder
            ->select(
                'uid',
                'pid',
                'title',
                'starttime',
                'valid_from',
                'valid_until',
                'lead_days',
                'week_label',
                'conversion_status',
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('valid_from', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );

        if ($storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
            );
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * First reference of the flipbook's pdf_file field.
     */
    public function findPdf(int $flipbookUid): ?FileReference
    {
        return $this->firstReference(self::TABLE, 'pdf_file', $flipbookUid);
    }

    /**
     * Image reference of the flipbook page with the lowest page number.
     */
    public function findCover(int $flipbookUid): ?FileReference
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PAGE_TABLE);
        $pageUid = $queryBuilder
            ->select('uid')
            ->from(self::PAGE_TABLE)
            ->where(
                $queryBuilder->expr()->eq('flipbook', $queryBuilder->createNamedParameter($flipbookUid, Connection::PARAM_INT)),
            )
            ->orderBy('page_number', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->addOrderBy('uid', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if ($pageUid === false) {
            return null;
        }

        return $this->firstReference(self::PAGE_TABLE, 'image', (int) $pageUid);
    }

    private function firstReference(string $table, string $field, int $uid): ?FileReference
    {
        $references = $this->fileRepository->findByRelation($table, $field, $uid);

        return $references[0] ?? null;
    }
}
