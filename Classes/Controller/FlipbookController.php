<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Controller;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class FlipbookController extends ActionController
{
    private const MANIFEST_PATH = 'EXT:digital_page_flip/Resources/Public/Build/.vite/manifest.json';

    public function __construct(
        private readonly FlipbookRepository $flipbookRepository,
        private readonly AssetCollector $assetCollector,
        private readonly LoggerInterface $logger,
    ) {}

    public function showAction(): ResponseInterface
    {
        $contentData = $this->request->getAttribute('currentContentObject')?->data ?? [];
        $flipbookUid = (int) ($contentData['tx_digitalpageflip_flipbook'] ?? 0);
        $flipbook = $this->flipbookRepository->findByUid($flipbookUid);

        if (!$flipbook instanceof Flipbook) {
            return $this->htmlResponse();
        }

        if ($flipbook->getConversionStatus() !== Flipbook::STATUS_COMPLETED) {
            $this->logger->info('Flipbook not ready for display.', [
                'flipbookUid' => $flipbook->getUid(),
                'status' => $flipbook->getConversionStatus(),
            ]);
            $this->view->assign('conversionPending', true);
            return $this->htmlResponse();
        }

        $this->injectViteAssets();
        $this->view->assign('flipbook', $flipbook);

        return $this->htmlResponse();
    }

    private function injectViteAssets(): void
    {
        $assets = $this->resolveViteAssets();

        if ($assets['js'] !== '') {
            $this->assetCollector->addJavaScript(
                'digital-page-flip',
                $assets['js'],
                ['type' => 'module'],
            );
        }

        if ($assets['css'] !== '') {
            $this->assetCollector->addStyleSheet(
                'digital-page-flip',
                $assets['css'],
            );
        }
    }
    /**
     * @return array{js: string, css: string}
     */
    private function resolveViteAssets(): array
    {
        $result = ['js' => '', 'css' => ''];
        $manifestFile = GeneralUtility::getFileAbsFileName(self::MANIFEST_PATH);

        if ($manifestFile === '' || !file_exists($manifestFile)) {
            return $result;
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            return $result;
        }

        $basePath = 'EXT:digital_page_flip/Resources/Public/Build/';

        foreach ($manifest as $entry) {
            if (!is_array($entry) || !isset($entry['file'])) {
                continue;
            }
            if (str_ends_with((string) $entry['file'], '.js')) {
                $result['js'] = $basePath . $entry['file'];
            }
            if (str_ends_with((string) $entry['file'], '.css')) {
                $result['css'] = $basePath . $entry['file'];
            }
        }

        return $result;
    }
}
