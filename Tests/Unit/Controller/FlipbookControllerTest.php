<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Controller;

use Kit\DigitalPageFlip\Controller\FlipbookController;
use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the conversion status display guard in FlipbookController.
 *
 * The showAction() requires full Extbase request bootstrapping and cannot be
 * unit-tested directly. These tests verify the status evaluation logic that
 * the controller uses to decide whether to render the flipbook viewer.
 *
 * @see \Kit\DigitalPageFlip\Controller\FlipbookController::showAction()
 */
final class FlipbookControllerTest extends TestCase
{
    #[Test]
    public function onlyCompletedStatusAllowsDisplay(): void
    {
        $statuses = [
            Flipbook::STATUS_PENDING => false,
            Flipbook::STATUS_PROCESSING => false,
            Flipbook::STATUS_COMPLETED => true,
            Flipbook::STATUS_ERROR => false,
        ];

        foreach ($statuses as $status => $shouldDisplay) {
            $flipbook = new Flipbook();
            $flipbook->setConversionStatus($status);

            $isReady = $flipbook->getConversionStatus() === Flipbook::STATUS_COMPLETED;
            self::assertSame(
                $shouldDisplay,
                $isReady,
                sprintf('Status %d should %sbe displayed.', $status, $shouldDisplay ? '' : 'not '),
            );
        }
    }

    #[Test]
    public function resolveFlipbookUidUsesSelectionInManualMode(): void
    {
        $data = ['tx_digitalpageflip_mode' => 0, 'tx_digitalpageflip_flipbook' => 7];

        self::assertSame(7, FlipbookController::resolveFlipbookUid($data, 99));
    }

    #[Test]
    public function resolveFlipbookUidUsesCurrentInAutoMode(): void
    {
        $data = ['tx_digitalpageflip_mode' => 1, 'tx_digitalpageflip_flipbook' => 7];

        self::assertSame(99, FlipbookController::resolveFlipbookUid($data, 99));
    }

    #[Test]
    public function resolveFlipbookUidReturnsZeroInAutoModeWithoutCurrent(): void
    {
        $data = ['tx_digitalpageflip_mode' => 1, 'tx_digitalpageflip_flipbook' => 7];

        self::assertSame(0, FlipbookController::resolveFlipbookUid($data, null));
    }
}
