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
     * @return QueryResultInterface<Flipbook>
     */
    public function findPending(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalOr(
                $query->equals('conversionStatus', Flipbook::STATUS_PENDING),
                $query->equals('conversionStatus', Flipbook::STATUS_ERROR),
            ),
        );

        return $query->execute();
    }
}
