<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Repository;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Flipbook>
 */
final class FlipbookRepository extends Repository
{
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Pending or failed conversions, including hidden and scheduled records, so a
     * flipbook is ready before its online start. Deleted records stay excluded.
     *
     * @return QueryResultInterface<int, Flipbook>
     */
    public function findPending(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->matching(
            $query->logicalOr(
                $query->equals('conversionStatus', Flipbook::STATUS_PENDING),
                $query->equals('conversionStatus', Flipbook::STATUS_ERROR),
            ),
        );

        return $query->execute();
    }

    /**
     * Looks up a flipbook for conversion regardless of hidden/starttime/endtime (deleted stays excluded).
     */
    public function findByUidForConversion(int $uid): ?Flipbook
    {
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setIgnoreEnableFields(true)
            ->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));

        $flipbook = $query->execute()->getFirst();

        return $flipbook instanceof Flipbook ? $flipbook : null;
    }
}
