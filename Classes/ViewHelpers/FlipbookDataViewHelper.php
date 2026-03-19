<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Model\Page;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class FlipbookDataViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('flipbook', Flipbook::class, 'The flipbook to render data for', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        /** @var Flipbook $flipbook */
        $flipbook = $arguments['flipbook'];
        $pages = [];

        /** @var Page $page */
        foreach ($flipbook->getPages() as $page) {
            $image = $page->getImage();
            if ($image !== null) {
                $originalResource = $image->getOriginalResource();
                if ($originalResource !== null) {
                    $pages[] = PathUtility::getAbsoluteWebPath($originalResource->getPublicUrl());
                }
            }
        }

        return htmlspecialchars(json_encode($pages, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
    }
}
