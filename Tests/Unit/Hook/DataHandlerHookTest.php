<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Tests\Unit\Hook;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Hook\DataHandlerHook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\DataHandling\DataHandler;

final class DataHandlerHookTest extends TestCase
{
    // ---------------------------------------------------------------
    // shouldConvert() — decision logic
    // ---------------------------------------------------------------

    #[Test]
    public function processingStatusAlwaysPreventsConversion(): void
    {
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_PROCESSING, false));
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_PROCESSING, true));
    }

    #[Test]
    public function completedStatusWithoutPdfChangeSkipsConversion(): void
    {
        self::assertFalse(DataHandlerHook::shouldConvert(Flipbook::STATUS_COMPLETED, false));
    }

    #[Test]
    public function completedStatusWithPdfChangeAllowsReconversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_COMPLETED, true));
    }

    #[Test]
    public function pendingStatusAllowsConversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_PENDING, false));
    }

    #[Test]
    public function errorStatusAllowsConversion(): void
    {
        self::assertTrue(DataHandlerHook::shouldConvert(Flipbook::STATUS_ERROR, false));
    }

    // ---------------------------------------------------------------
    // PDF change detection via DataHandler datamap
    // ---------------------------------------------------------------

    #[Test]
    public function pdfChangedDetectionViaDatamap(): void
    {
        $dataHandler = new DataHandler();
        $dataHandler->datamap = [
            'tx_digitalpageflip_domain_model_flipbook' => [
                42 => ['pdf_file' => 1],
            ],
        ];

        self::assertTrue(isset($dataHandler->datamap['tx_digitalpageflip_domain_model_flipbook'][42]['pdf_file']));
    }

    #[Test]
    public function noPdfChangeDetectionViaDatamap(): void
    {
        $dataHandler = new DataHandler();
        $dataHandler->datamap = [
            'tx_digitalpageflip_domain_model_flipbook' => [
                42 => ['title' => 'Updated Title'],
            ],
        ];

        self::assertFalse(isset($dataHandler->datamap['tx_digitalpageflip_domain_model_flipbook'][42]['pdf_file']));
    }
}
