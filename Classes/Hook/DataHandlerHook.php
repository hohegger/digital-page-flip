<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Hook;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Validity\ValidityCalculator;
use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DataHandlerHook
{
    private const TABLE = 'tx_digitalpageflip_domain_model_flipbook';
    private const DEFAULT_LEAD_DAYS = 2;

    /**
     * Derives starttime/endtime (and a missing valid_until) just before the DB write.
     * Runs after DataHandler's access checks, so exclude fields cannot strip the derived values.
     *
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        string|int $id,
        array &$fieldArray,
        DataHandler $dataHandler,
    ): void {
        if ($table !== self::TABLE || $fieldArray === []) {
            return;
        }

        $current = [];
        if ($status !== 'new') {
            $current = BackendUtility::getRecord(self::TABLE, (int) $id) ?? [];
        }

        $fieldArray = array_merge(
            $fieldArray,
            self::deriveSchedule($fieldArray, $current, new DateTimeZone(date_default_timezone_get())),
        );
    }

    /**
     * Returns the additional fields to write, or an empty array if the record is not validity-controlled.
     *
     * @param array<string, mixed> $incoming
     * @param array<string, mixed> $current
     * @return array<string, int>
     */
    public static function deriveSchedule(array $incoming, array $current, DateTimeZone $timezone): array
    {
        $validFrom = self::normalizeTimestamp($incoming['valid_from'] ?? $current['valid_from'] ?? 0);
        if ($validFrom <= 0) {
            return [];
        }

        $rawLeadDays = $incoming['lead_days'] ?? $current['lead_days'] ?? '';
        $leadDays = $rawLeadDays === '' ? self::DEFAULT_LEAD_DAYS : (int) $rawLeadDays;

        $result = [
            'starttime' => ValidityCalculator::onlineStart($validFrom, $leadDays, $timezone),
            'endtime' => 0,
        ];

        $validUntil = self::normalizeTimestamp($incoming['valid_until'] ?? $current['valid_until'] ?? 0);
        if ($validUntil <= 0) {
            $result['valid_until'] = ValidityCalculator::defaultValidUntil($validFrom, $timezone);
        }

        return $result;
    }

    private static function normalizeTimestamp(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            if (ctype_digit($value)) {
                return (int) $value;
            }
            try {
                return (new DateTimeImmutable($value))->getTimestamp();
            } catch (Exception) {
                return 0;
            }
        }

        return 0;
    }

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
        $connectionPool = $container->get(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE);

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

        $connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            ['conversion_status' => Flipbook::STATUS_PENDING],
            ['uid' => $uid],
        );

        $this->addFlashMessage(
            'Die PDF-Konvertierung wurde eingeplant und startet in Kürze automatisch.',
            ContextualFeedbackSeverity::INFO,
        );
    }

    /**
     * Called after DataHandler processes command map operations (delete, copy, move).
     * Cleans up FAL files and references when a flipbook is deleted.
     *
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
