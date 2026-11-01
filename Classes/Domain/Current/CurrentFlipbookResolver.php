<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Current;

use DateTimeZone;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * Answers "which flipbook is online now" for a site, based on validity and conversion state.
 */
final readonly class CurrentFlipbookResolver
{
    public const CACHE_TAG = CurrentFlipbookFinder::TABLE;
    public const STORAGE_PID_SETTING = 'plugin.tx_digitalpageflip.persistence.storagePid';

    public function __construct(
        private CurrentFlipbookFinder $finder,
        private Context $context,
    ) {}

    public function resolveCurrentUid(SiteInterface $site, ?ServerRequestInterface $request = null): ?int
    {
        $this->addCacheTag($request);
        $row = CurrentFlipbookSelector::selectCurrent(
            $this->finder->fetchCandidates($this->storagePid($site), false),
            $this->now(),
        );

        return $row === null ? null : (int) $row['uid'];
    }

    public function resolveCurrent(SiteInterface $site, ?ServerRequestInterface $request = null): ?CurrentFlipbook
    {
        $this->addCacheTag($request);
        $row = CurrentFlipbookSelector::selectCurrent(
            $this->finder->fetchCandidates($this->storagePid($site), false),
            $this->now(),
        );

        return $row === null ? null : $this->createFlipbook($row);
    }

    public function resolveUpcoming(SiteInterface $site, ?ServerRequestInterface $request = null): ?CurrentFlipbook
    {
        $this->addCacheTag($request);
        $row = CurrentFlipbookSelector::selectUpcoming(
            $this->finder->fetchCandidates($this->storagePid($site), true),
            $this->now(),
        );

        return $row === null ? null : $this->createFlipbook($row);
    }

    /**
     * Timestamp of the next online start after $now, or null if none is scheduled.
     */
    public function nextOnlineStart(int $storagePid, int $now): ?int
    {
        $row = CurrentFlipbookSelector::selectUpcoming($this->finder->fetchCandidates($storagePid, true), $now);

        return $row === null ? null : (int) $row['starttime'];
    }

    /**
     * Storage folder from the site setting; 0 (= all pages) for sites without settings.
     */
    public function storagePid(SiteInterface $site): int
    {
        if (!$site instanceof Site) {
            return 0;
        }
        $value = $site->getSettings()->get(self::STORAGE_PID_SETTING, 0);

        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createFlipbook(array $row): CurrentFlipbook
    {
        $uid = (int) $row['uid'];

        return CurrentFlipbook::fromRow(
            $row,
            $this->finder->findPdf($uid),
            $this->finder->findCover($uid),
            new DateTimeZone(date_default_timezone_get()),
        );
    }

    private function now(): int
    {
        return (int) $this->context->getPropertyFromAspect('date', 'timestamp');
    }

    private function addCacheTag(?ServerRequestInterface $request): void
    {
        $collector = $request?->getAttribute('frontend.cache.collector');
        if ($collector instanceof CacheDataCollector) {
            $collector->addCacheTags(new CacheTag(self::CACHE_TAG));
        }
    }
}
