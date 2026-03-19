<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\EventListener;

use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;

final class AfterFileAddedListener
{
    public function __invoke(AfterFileAddedEvent $event): void
    {
        // Future: Auto-trigger conversion when PDF is uploaded to specific folder
    }
}
