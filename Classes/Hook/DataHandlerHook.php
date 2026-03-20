<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Hook;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use Throwable;
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
            if (str_starts_with((string) $id, 'NEW')) {
                $uid = (int) ($dataHandler->substNEWwithIDs[$id] ?? 0);
            } else {
                $uid = (int) $id;
            }

            if ($uid <= 0) {
                continue;
            }

            $this->processFlipbook($uid, $dataHandler);
        }
    }

    private function processFlipbook(int $uid, DataHandler $dataHandler): void
    {
        $container = GeneralUtility::getContainer();
        $queryBuilder = $container->get(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $record = $queryBuilder
            ->select('pdf_file', 'conversion_status', 'page_count')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($record === false) {
            return;
        }

        $pdfCount = (int) $record['pdf_file'];
        $status = (int) $record['conversion_status'];

        if ($pdfCount === 0) {
            return;
        }

        $pdfChanged = isset($dataHandler->datamap[self::TABLE][$uid]['pdf_file']);
        if (!self::shouldConvert($status, $pdfChanged)) {
            return;
        }

        $conversionService = $container->get(PdfConversionService::class);
        $flipbookRepository = $container->get(FlipbookRepository::class);
        $persistenceManager = $container->get(PersistenceManager::class);
        $logger = $container->get(LogManager::class)->getLogger(self::class);

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
                ContextualFeedbackSeverity::OK,
            );
        } catch (Throwable $e) {
            $logger->error('Automatic PDF conversion failed.', [
                'flipbookUid' => $uid,
                'error' => $e->getMessage(),
            ]);

            $this->addFlashMessage(
                sprintf('PDF-Konvertierung fehlgeschlagen: %s', $e->getMessage()),
                ContextualFeedbackSeverity::ERROR,
            );
        }
    }

    /**
     * Called after DataHandler processes command map operations (delete, copy, move).
     * Cleans up FAL files and references when a flipbook is deleted.
     */
    /**
     * @param array<string, mixed> $pasteDatamap
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        int|string $id,
        mixed $value,
        DataHandler $dataHandler,
        mixed $pasteUpdate,
        array $pasteDatamap,
    ): void {
        if ($table !== self::TABLE || $command !== 'delete') {
            return;
        }

        $uid = (int) $id;
        if ($uid <= 0) {
            return;
        }

        try {
            $container = GeneralUtility::getContainer();
            $cleanupService = $container->get(FlipbookCleanupService::class);
            $cleanupService->cleanupForDeletion($uid);
        } catch (Throwable $e) {
            $logger = GeneralUtility::getContainer()->get(LogManager::class)->getLogger(self::class);
            $logger->error('FAL cleanup after flipbook deletion failed.', [
                'flipbookUid' => $uid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determines whether a flipbook should be (re-)converted based on its status and whether the PDF changed.
     */
    public static function shouldConvert(int $status, bool $pdfChanged): bool
    {
        if ($status === Flipbook::STATUS_PROCESSING) {
            return false;
        }

        if ($status === Flipbook::STATUS_COMPLETED && !$pdfChanged) {
            return false;
        }

        return true;
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity): void
    {
        $flashMessage = new FlashMessage($message, 'PDF-Konvertierung', $severity, true);
        GeneralUtility::getContainer()->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->enqueue($flashMessage);
    }
}
