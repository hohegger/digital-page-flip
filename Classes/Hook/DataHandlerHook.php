<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Hook;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class DataHandlerHook
{
    private const TABLE = 'tx_digitalpageflip_domain_model_flipbook';

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (!isset($dataHandler->datamap[self::TABLE])) {
            return;
        }

        foreach ($dataHandler->datamap[self::TABLE] as $id => $fields) {
            if (str_starts_with((string)$id, 'NEW')) {
                $uid = (int)($dataHandler->substNEWwithIDs[$id] ?? 0);
            } else {
                $uid = (int)$id;
            }

            if ($uid <= 0) {
                continue;
            }

            $this->processFlipbook($uid);
        }
    }

    private function processFlipbook(int $uid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $record = $queryBuilder
            ->select('pdf_file', 'conversion_status', 'page_count')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($record === false) {
            return;
        }

        $pdfCount = (int)$record['pdf_file'];
        $status = (int)$record['conversion_status'];

        if ($pdfCount === 0) {
            return;
        }

        if ($status === Flipbook::STATUS_PROCESSING || $status === Flipbook::STATUS_COMPLETED) {
            return;
        }

        $container = GeneralUtility::getContainer();
        $conversionService = $container->get(PdfConversionService::class);
        $flipbookRepository = $container->get(FlipbookRepository::class);
        $persistenceManager = $container->get(PersistenceManager::class);
        $logger = $container->get(LogManager::class)->getLogger(__CLASS__);

        $flipbook = $flipbookRepository->findByUid($uid);
        if ($flipbook === null) {
            return;
        }

        try {
            $conversionService->convert($flipbook);
            $persistenceManager->persistAll();

            $logger->info('Automatic PDF conversion completed.', [
                'flipbookUid' => $uid,
                'pageCount' => $flipbook->getPageCount(),
            ]);

            $this->addFlashMessage(
                sprintf('PDF wurde erfolgreich konvertiert. %d Seiten generiert.', $flipbook->getPageCount()),
                ContextualFeedbackSeverity::OK
            );
        } catch (\Throwable $e) {
            $logger->error('Automatic PDF conversion failed.', [
                'flipbookUid' => $uid,
                'error' => $e->getMessage(),
            ]);

            $this->addFlashMessage(
                sprintf('PDF-Konvertierung fehlgeschlagen: %s', $e->getMessage()),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            'PDF-Konvertierung',
            $severity,
            true
        );
        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->enqueue($flashMessage);
    }
}
