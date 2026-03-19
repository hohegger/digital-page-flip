<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Controller;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class FlipbookController extends ActionController
{
    private const MANIFEST_PATH = 'EXT:digital_page_flip/Resources/Public/Build/.vite/manifest.json';

    public function __construct(
        private readonly FlipbookRepository $flipbookRepository,
        private readonly AssetCollector $assetCollector,
    ) {}

    public function listAction(): ResponseInterface
    {
        $flipbookUid = (int)($this->settings['flipbookUid'] ?? 0);

        if ($flipbookUid > 0) {
            $flipbook = $this->flipbookRepository->findByUid($flipbookUid);
            if ($flipbook instanceof Flipbook) {
                return $this->showFlipbook($flipbook);
            }
        }

        $flipbooks = $this->flipbookRepository->findPublished();
        $this->view->assign('flipbooks', $flipbooks);

        return $this->htmlResponse();
    }

    public function showAction(Flipbook $flipbook): ResponseInterface
    {
        return $this->showFlipbook($flipbook);
    }

    private function showFlipbook(Flipbook $flipbook): ResponseInterface
    {
        $assets = $this->resolveViteAssets();

        if ($assets['js'] !== '') {
            $this->assetCollector->addJavaScript(
                'digital-page-flip',
                $assets['js'],
                ['type' => 'module']
            );
        }

        if ($assets['css'] !== '') {
            $this->assetCollector->addStyleSheet(
                'digital-page-flip',
                $assets['css']
            );
        }

        $this->view->assign('flipbook', $flipbook);
        $this->view->setTemplate('Flipbook/Show');

        return $this->htmlResponse();
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

        $manifest = json_decode((string)file_get_contents($manifestFile), true);
        if (!is_array($manifest)) {
            return $result;
        }

        $basePath = 'EXT:digital_page_flip/Resources/Public/Build/';

        foreach ($manifest as $entry) {
            if (!is_array($entry) || !isset($entry['file'])) {
                continue;
            }
            if (str_ends_with($entry['file'], '.js')) {
                $result['js'] = $basePath . $entry['file'];
            }
            if (str_ends_with($entry['file'], '.css')) {
                $result['css'] = $basePath . $entry['file'];
            }
        }

        return $result;
    }
}
