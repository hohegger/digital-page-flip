<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\ViewHelpers;

use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbook;
use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbookResolver;
use Kit\DigitalPageFlip\ViewHelpers\CurrentFlipbookViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

final class CurrentFlipbookViewHelperTest extends TestCase
{
    #[Test]
    public function argumentsAreRegistered(): void
    {
        $viewHelper = $this->createViewHelper($this->createMock(CurrentFlipbookResolver::class), $this->createMock(SiteFinder::class));

        $arguments = $viewHelper->prepareArguments();

        self::assertArrayHasKey('upcoming', $arguments);
        self::assertArrayHasKey('pageUid', $arguments);
        self::assertFalse($arguments['upcoming']->getDefaultValue());
        self::assertSame(0, $arguments['pageUid']->getDefaultValue());
    }

    #[Test]
    public function rendersCurrentFlipbookOfRequestSite(): void
    {
        $site = $this->createMock(Site::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);
        $expected = $this->createMock(CurrentFlipbook::class);
        $resolver = $this->createMock(CurrentFlipbookResolver::class);
        $resolver->expects(self::once())->method('resolveCurrent')->with($site, $request)->willReturn($expected);
        $resolver->expects(self::never())->method('resolveUpcoming');

        $viewHelper = $this->createViewHelper($resolver, $this->createMock(SiteFinder::class), $request);

        self::assertSame($expected, $viewHelper->render());
    }

    #[Test]
    public function rendersUpcomingFlipbookWhenRequested(): void
    {
        $site = $this->createMock(Site::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('site')->willReturn($site);
        $expected = $this->createMock(CurrentFlipbook::class);
        $resolver = $this->createMock(CurrentFlipbookResolver::class);
        $resolver->expects(self::once())->method('resolveUpcoming')->with($site, $request)->willReturn($expected);
        $resolver->expects(self::never())->method('resolveCurrent');

        $viewHelper = $this->createViewHelper($resolver, $this->createMock(SiteFinder::class), $request, ['upcoming' => true]);

        self::assertSame($expected, $viewHelper->render());
    }

    #[Test]
    public function usesPageUidToFindSiteWithoutRequestSite(): void
    {
        $site = $this->createMock(Site::class);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->expects(self::once())->method('getSiteByPageId')->with(12)->willReturn($site);
        $resolver = $this->createMock(CurrentFlipbookResolver::class);
        $resolver->expects(self::once())->method('resolveCurrent')->with($site, null)->willReturn(null);

        $viewHelper = $this->createViewHelper($resolver, $siteFinder, null, ['pageUid' => 12]);

        self::assertNull($viewHelper->render());
    }

    #[Test]
    public function returnsNullWhenSiteCannotBeDetermined(): void
    {
        $resolver = $this->createMock(CurrentFlipbookResolver::class);
        $resolver->expects(self::never())->method('resolveCurrent');
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('none', 1));

        self::assertNull($this->createViewHelper($resolver, $siteFinder, null, ['pageUid' => 12])->render());
        self::assertNull($this->createViewHelper($resolver, $siteFinder)->render());
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function createViewHelper(
        CurrentFlipbookResolver $resolver,
        SiteFinder $siteFinder,
        ?ServerRequestInterface $request = null,
        array $arguments = [],
    ): CurrentFlipbookViewHelper {
        $context = $this->createMock(RenderingContextInterface::class);
        $context->method('hasAttribute')->willReturn($request !== null);
        if ($request !== null) {
            $context->method('getAttribute')->willReturn($request);
        }

        $viewHelper = new CurrentFlipbookViewHelper($resolver, $siteFinder);
        $viewHelper->setRenderingContext($context);
        $viewHelper->setArguments($arguments + ['upcoming' => false, 'pageUid' => 0]);

        return $viewHelper;
    }
}
