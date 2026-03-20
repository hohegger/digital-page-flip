<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Service;

use FilesystemIterator;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final readonly class PdfConversionService
{
    private const DEFAULT_GS_PATH = '/usr/bin/gs';
    private const DEFAULT_RESOLUTION = 300;
    private const DEFAULT_WEBP_QUALITY = 90;
    private const DEFAULT_MAX_FILE_SIZE_MB = 50;
    private const GS_TIMEOUT_SECONDS = 120;
    private const TARGET_FOLDER_PREFIX = 'user_upload/tx_digitalpageflip/flipbook_';
    public function __construct(
        private ResourceFactory $resourceFactory,
        private StorageRepository $storageRepository,
        private FlipbookRepository $flipbookRepository,
        private PersistenceManager $persistenceManager,
        private FlipbookCleanupService $cleanupService,
        private LoggerInterface $logger,
    ) {}
    /**
     * Converts a PDF attached to the given flipbook into page images (WebP + PNG fallback).
     *
     * @param array<string, mixed> $settings TypoScript settings for gs path, resolution, quality
     */
    public function convert(Flipbook $flipbook, array $settings = []): void
    {
        $tempDir = '';

        try {
            $pdfFileReference = $flipbook->getPdfFile();
            if ($pdfFileReference === null) {
                throw new RuntimeException('Flipbook has no PDF file attached.', 1_710_000_001);
            }

            $this->validatePdf($pdfFileReference);
            $this->validateFileSize($pdfFileReference, $settings);

            $flipbook->setConversionStatus(Flipbook::STATUS_PROCESSING);
            $this->flipbookRepository->update($flipbook);
            $this->persistenceManager->persistAll();

            // Clean up existing pages before re-conversion
            if ($flipbook->getUid() !== null) {
                $this->cleanupService->cleanupForReconversion($flipbook->getUid());
                $flipbook->setPages(new ObjectStorage());
                $flipbook->setPageCount(0);
                $this->flipbookRepository->update($flipbook);
                $this->persistenceManager->persistAll();
            }

            $originalFile = $pdfFileReference->getOriginalResource()->getOriginalFile();
            $pdfLocalPath = $originalFile->getForLocalProcessing(false);

            $tempDir = $this->createTempDirectory();

            $gsPath = $this->resolveGhostscriptPath($settings);
            $resolution = (int) ($settings['imageResolution'] ?? self::DEFAULT_RESOLUTION);
            $webpQuality = (int) ($settings['webpQuality'] ?? self::DEFAULT_WEBP_QUALITY);

            $pageCount = $this->executeGhostscript($gsPath, $pdfLocalPath, $tempDir, $resolution);

            if ($pageCount === 0) {
                throw new RuntimeException('Ghostscript produced no output pages.', 1_710_000_002);
            }

            $this->normalizePageDimensions($tempDir, $pageCount);

            $storage = $this->storageRepository->getDefaultStorage();
            if ($storage === null) {
                throw new RuntimeException('No default FAL storage available.', 1_710_000_003);
            }

            $targetFolderPath = self::TARGET_FOLDER_PREFIX . $flipbook->getUid() . '/';

            if (!$storage->hasFolder($targetFolderPath)) {
                $storage->createFolder($targetFolderPath);
            }

            $targetFolder = $storage->getFolder($targetFolderPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $pngFileName = sprintf('page_%03d.png', $i);
                $webpFileName = sprintf('page_%03d.webp', $i);

                $pngPath = $tempDir . '/' . $pngFileName;
                $webpPath = $tempDir . '/' . $webpFileName;

                if (!file_exists($pngPath)) {
                    $this->logger->warning('Expected PNG file not found, skipping page.', [
                        'page' => $i,
                        'path' => $pngPath,
                    ]);
                    continue;
                }

                $this->convertToWebP($pngPath, $webpPath, $webpQuality);

                $webpFile = $this->registerFileInFal($webpPath, $targetFolder, $webpFileName);

                $page = new Page();
                $page->setPageNumber($i);
                $page->setSorting($i);
                $page->setImage($this->createFileReference($webpFile));

                $flipbook->addPage($page);
            }

            $flipbook->setPageCount($pageCount);
            $flipbook->setConversionStatus(Flipbook::STATUS_COMPLETED);
            $this->flipbookRepository->update($flipbook);
            $this->persistenceManager->persistAll();

            $this->logger->info('PDF conversion completed successfully.', [
                'flipbookUid' => $flipbook->getUid(),
                'pageCount' => $pageCount,
            ]);
        } catch (Throwable $e) {
            $flipbook->setConversionStatus(Flipbook::STATUS_ERROR);
            $this->flipbookRepository->update($flipbook);
            $this->persistenceManager->persistAll();

            $this->logger->error('PDF conversion failed.', [
                'flipbookUid' => $flipbook->getUid(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        } finally {
            if ($tempDir !== '' && is_dir($tempDir)) {
                $this->cleanupTempDirectory($tempDir);
            }
        }
    }
    /**
     * Validates that the attached file is a genuine PDF.
     */
    private function validatePdf(FileReference $fileReference): void
    {
        $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
        $mimeType = $originalFile->getMimeType();

        if ($mimeType !== 'application/pdf') {
            throw new RuntimeException(
                sprintf('Invalid MIME type "%s", expected "application/pdf".', $mimeType),
                1_710_000_010,
            );
        }
    }

    /**
     * Validates that the PDF does not exceed the configured maximum file size.
     *
     * @param array<string, mixed> $settings
     */
    private function validateFileSize(FileReference $fileReference, array $settings): void
    {
        $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
        $maxMb = max(1, (int) ($settings['maxFileSize'] ?? self::DEFAULT_MAX_FILE_SIZE_MB));
        $maxBytes = $maxMb * 1024 * 1024;
        $fileSize = $originalFile->getSize();

        if ($fileSize > $maxBytes) {
            throw new RuntimeException(
                sprintf('PDF exceeds maximum file size of %d MB (actual: %d MB).', $maxMb, (int) ceil($fileSize / 1024 / 1024)),
                1_710_000_011,
            );
        }
    }

    /**
     * Executes Ghostscript to rasterize the PDF into PNG pages.
     *
     * @return int Number of pages generated
     */
    private function executeGhostscript(
        string $gsPath,
        string $pdfPath,
        string $outputDir,
        int $resolution,
    ): int {
        $outputPattern = $outputDir . '/page_%03d.png';

        $command = sprintf(
            '%s -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r%d -sOutputFile=%s %s',
            escapeshellarg($gsPath),
            $resolution,
            escapeshellarg($outputPattern),
            escapeshellarg($pdfPath),
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start Ghostscript process.', 1_710_000_020);
        }

        fclose($pipes[0]);

        $startTime = time();
        $output = '';
        $timedOut = false;

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if ((time() - $startTime) > self::GS_TIMEOUT_SECONDS) {
                $timedOut = true;
                proc_terminate($process, 9);
                break;
            }
            $output .= stream_get_contents($pipes[1]);
            usleep(100_000);
        }

        $output .= stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($timedOut) {
            proc_close($process);
            throw new RuntimeException(
                sprintf('Ghostscript timed out after %d seconds.', self::GS_TIMEOUT_SECONDS),
                1_710_000_021,
            );
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $combinedOutput = trim($output . "\n" . $stderr);
            $this->logger->error('Ghostscript exited with error.', [
                'exitCode' => $exitCode,
                'output' => mb_substr($combinedOutput, 0, 2000),
            ]);
            throw new RuntimeException(
                sprintf('Ghostscript exited with code %d: %s', $exitCode, mb_substr($combinedOutput, 0, 500)),
                1_710_000_022,
            );
        }

        $generatedFiles = glob($outputDir . '/page_*.png');

        return $generatedFiles !== false ? count($generatedFiles) : 0;
    }
    /**
     * Normalizes all generated PNGs to the dimensions of the first page.
     * PDFs can have pages with different sizes — this ensures uniform flipbook rendering.
     */
    private function normalizePageDimensions(string $outputDir, int $pageCount): void
    {
        $firstPagePath = $outputDir . '/page_001.png';
        if (!file_exists($firstPagePath)) {
            return;
        }

        $size = getimagesize($firstPagePath);
        if ($size === false) {
            return;
        }

        $targetWidth = $size[0];
        $targetHeight = $size[1];

        for ($i = 2; $i <= $pageCount; $i++) {
            $pagePath = sprintf('%s/page_%03d.png', $outputDir, $i);
            if (!file_exists($pagePath)) {
                continue;
            }

            $pageSize = getimagesize($pagePath);
            if ($pageSize === false || ($pageSize[0] === $targetWidth && $pageSize[1] === $targetHeight)) {
                continue;
            }

            $command = sprintf(
                'convert %s -resize %dx%d! %s 2>&1',
                escapeshellarg($pagePath),
                $targetWidth,
                $targetHeight,
                escapeshellarg($pagePath),
            );

            $resizeOutput = [];
            $resizeExitCode = 0;
            exec($command, $resizeOutput, $resizeExitCode);

            if ($resizeExitCode !== 0) {
                $this->logger->warning('ImageMagick resize failed for page.', [
                    'page' => $i,
                    'exitCode' => $resizeExitCode,
                    'output' => implode("\n", $resizeOutput),
                ]);
                continue;
            }

            $this->logger->info('Normalized page dimensions.', [
                'page' => $i,
                'from' => $pageSize[0] . 'x' . $pageSize[1],
                'to' => $targetWidth . 'x' . $targetHeight,
            ]);
        }
    }
    /**
     * Converts a PNG image to WebP format via ImageMagick.
     */
    private function convertToWebP(string $pngPath, string $webpPath, int $quality): void
    {
        $command = sprintf(
            'convert %s -quality %d %s 2>&1',
            escapeshellarg($pngPath),
            $quality,
            escapeshellarg($webpPath),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                sprintf(
                    'ImageMagick WebP conversion failed (exit code %d): %s',
                    $exitCode,
                    implode("\n", $output),
                ),
                1_710_000_030,
            );
        }

        if (!file_exists($webpPath)) {
            throw new RuntimeException(
                sprintf('WebP file was not created at expected path: %s', $webpPath),
                1_710_000_031,
            );
        }
    }
    /**
     * Registers a local file in FAL by adding it to the given folder.
     */
    private function registerFileInFal(
        string $localFilePath,
        Folder $targetFolder,
        string $fileName,
    ): File {
        $storage = $targetFolder->getStorage();

        /** @var File */
        return $storage->addFile(
            $localFilePath,
            $targetFolder,
            $fileName,
            DuplicationBehavior::REPLACE,
        );
    }
    /**
     * Creates an Extbase FileReference from a FAL File object via sys_file_reference record.
     */
    private function createFileReference(File $falFile): FileReference
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_reference');

        $connection->insert('sys_file_reference', [
            'uid_local' => $falFile->getUid(),
            'uid_foreign' => 0,
            'tablenames' => 'tx_digitalpageflip_domain_model_page',
            'fieldname' => '',
            'tstamp' => time(),
            'crdate' => time(),
            'pid' => 0,
        ]);

        $referenceUid = (int) $connection->lastInsertId();

        $coreFileReference = $this->resourceFactory->getFileReferenceObject($referenceUid);

        /** @var FileReference $extbaseFileReference */
        $extbaseFileReference = GeneralUtility::makeInstance(FileReference::class);
        $extbaseFileReference->setOriginalResource($coreFileReference);

        return $extbaseFileReference;
    }
    /**
     * Creates a temporary directory for conversion output.
     */
    private function createTempDirectory(): string
    {
        $basePath = Environment::getVarPath() . '/transient';

        if (!is_dir($basePath)) {
            GeneralUtility::mkdir_deep($basePath);
        }

        $tempDir = $basePath . '/tx_digitalpageflip_' . bin2hex(random_bytes(8));

        if (!mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException(
                sprintf('Failed to create temporary directory: %s', $tempDir),
                1_710_000_040,
            );
        }

        return $tempDir;
    }
    /**
     * Removes the temporary directory and all its contents.
     */
    private function cleanupTempDirectory(string $path): void
    {
        $realPath = realpath($path);
        $varPath = realpath(Environment::getVarPath());

        if ($realPath === false || $varPath === false || !str_starts_with($realPath, $varPath)) {
            $this->logger->warning('Refused to clean up directory outside of var path.', [
                'path' => $path,
            ]);
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realPath, RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }

        rmdir($realPath);
    }
    /**
     * Resolves the Ghostscript binary path from settings or falls back to default.
     *
     * @param array<string, mixed> $settings
     */
    private function resolveGhostscriptPath(array $settings): string
    {
        $gsPath = (string) ($settings['ghostscriptPath'] ?? self::DEFAULT_GS_PATH);

        if ($gsPath === '' || !is_executable($gsPath)) {
            if (is_executable(self::DEFAULT_GS_PATH)) {
                return self::DEFAULT_GS_PATH;
            }
            throw new RuntimeException(
                sprintf('Ghostscript binary not found or not executable at: %s', $gsPath),
                1_710_000_050,
            );
        }

        return $gsPath;
    }
}
