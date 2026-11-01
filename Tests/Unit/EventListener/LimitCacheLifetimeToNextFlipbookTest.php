<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\EventListener;

use Kit\DigitalPageFlip\EventListener\LimitCacheLifetimeToNextFlipbook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LimitCacheLifetimeToNextFlipbookTest extends TestCase
{
    private const NOW = 1_700_000_000;

    #[Test]
    public function cacheLifetimeIsCappedAtNextOnlineStart(): void
    {
        self::assertSame(600, LimitCacheLifetimeToNextFlipbook::cappedLifetime(86400, self::NOW + 600, self::NOW));
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
