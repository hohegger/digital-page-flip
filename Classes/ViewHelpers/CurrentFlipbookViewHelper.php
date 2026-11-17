<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbook;
use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbookResolver;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns the current (or with upcoming="1" the next scheduled) flipbook, or null if there is none.
 *
 * Usage: <f:variable name="handout" value="{dpf:currentFlipbook()}" />
 */
final class CurrentFlipbookViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(
        private readonly CurrentFlipbookResolver $resolver,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('upcoming', 'bool', 'Return the next scheduled flipbook instead of the current one', false, false);
        $this->registerArgument('pageUid', 'int', 'Page to determine the site from when there is no frontend request (backend preview)', false, 0);
    }

    public function render(): ?CurrentFlipbook
    {
        $context = $this->renderingContext;
        $request = $context !== null && $context->hasAttribute(ServerRequestInterface::class)
            ? $context->getAttribute(ServerRequestInterface::class)
            : null;

        $site = $request?->getAttribute('site');
        if (!$site instanceof Site) {
            $pageUid = (int) $this->arguments['pageUid'];
            if ($pageUid <= 0) {
                return null;
            }
            try {
                $site = $this->siteFinder->getSiteByPageId($pageUid);
            } catch (SiteNotFoundException) {
                return null;
            }
        }

        return $this->arguments['upcoming']
            ? $this->resolver->resolveUpcoming($site, $request)
            : $this->resolver->resolveCurrent($site, $request);
    }
}
