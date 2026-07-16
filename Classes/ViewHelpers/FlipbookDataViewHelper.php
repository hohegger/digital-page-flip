<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class FlipbookDataViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('flipbook', Flipbook::class, 'The flipbook to render data for', true);
    }

    public function render(): string
    {
        /** @var Flipbook $flipbook */
        $flipbook = $this->arguments['flipbook'];
        $pages = [];

        /** @var Page $page */
        foreach ($flipbook->getPages() as $page) {
            $image = $page->getImage();
            if ($image === null) {
                continue;
            }

            try {
                $originalResource = $image->getOriginalResource();
            } catch (ResourceDoesNotExistException) {
                // Fehlende sys_file_reference (z. B. gelöschte Datei): Seite überspringen
                // statt das Frontend-Rendering abbrechen zu lassen.
                continue;
            }

            $publicUrl = $originalResource->getPublicUrl();
            if ($publicUrl !== null) {
                $pages[] = PathUtility::getAbsoluteWebPath($publicUrl);
            }
        }

        return htmlspecialchars(json_encode($pages, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
    }
}
