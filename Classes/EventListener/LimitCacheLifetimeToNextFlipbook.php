<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\EventListener;

use Kit\DigitalPageFlip\Domain\Current\CurrentFlipbookResolver;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;

/**
 * Makes every page cache entry expire when the next flipbook goes online,
 * so pages showing the "current" flipbook re-render without a manual flush.
 */
#[AsEventListener(identifier: 'digital-page-flip/limit-cache-lifetime-to-next-flipbook')]
final readonly class LimitCacheLifetimeToNextFlipbook
{
    public function __construct(
        private CurrentFlipbookResolver $resolver,
    ) {}

    public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
    {
        $now = (int) $event->getContext()->getPropertyFromAspect('date', 'timestamp');
        // Records become visible by the minute-rounded access time (SIM_ACCESS_TIME), not by the second.
        $accessTime = $now - $now % 60;
        $event->setCacheLifetime(
            self::cappedLifetime($event->getCacheLifetime(), $this->resolver->nextOnlineStart(0, $accessTime), $now),
        );
    }

    /**
     * Caps the lifetime at the next online start, rounded up to the full minute
     * (the first moment the minute-based start time restriction shows the record).
     */
    public static function cappedLifetime(int $current, ?int $nextOnlineStart, int $now): int
    {
        if ($nextOnlineStart === null) {
            return $current;
        }

        $visibleFrom = intdiv($nextOnlineStart + 59, 60) * 60;

        return max(1, min($current, $visibleFrom - $now));
    }
}
