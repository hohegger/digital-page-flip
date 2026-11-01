<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\EventListener;

use Kit\DigitalPageFlip\EventListener\LimitCacheLifetimeToNextFlipbook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LimitCacheLifetimeToNextFlipbookTest extends TestCase
{
    /** Minute-aligned like the derived online starts (00:00). */
    private const NOW = 1_699_999_980;

    #[Test]
    public function cacheLifetimeIsCappedAtNextOnlineStart(): void
    {
        self::assertSame(600, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW + 600, self::NOW));
    }

    #[Test]
    public function cacheLifetimeRoundsUpToFullMinute(): void
    {
        // Start 15:30:07 is only visible from 15:31:00 (minute-rounded access time).
        self::assertSame(660, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW + 607, self::NOW));
        self::assertSame(60, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW + 1, self::NOW));
    }

    #[Test]
    public function cacheLifetimeUnchangedWithoutUpcoming(): void
    {
        self::assertSame(86400, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, null, self::NOW));
    }

    #[Test]
    public function cacheLifetimeUnchangedWhenNextIsLater(): void
    {
        self::assertSame(86400, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW + 200_000, self::NOW));
    }

    #[Test]
    public function cacheLifetimeNeverBelowOneSecond(): void
    {
        self::assertSame(1, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW - 50, self::NOW));
        self::assertSame(1, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW, self::NOW));
    }
}
