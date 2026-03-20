<?php

declare(strict_types=1);

use Kit\DigitalPageFlip\Controller\FlipbookController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'DigitalPageFlip',
    'Flipbook',
    [
        FlipbookController::class => 'list,show',
    ],
    [
        FlipbookController::class => '',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

// @todo TYPO3 v13: Migrate to PSR-14 DataHandler events and remove these SC_OPTIONS hooks.
// These hooks have no PSR-14 replacement in v12 but are removed in v13.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = \Kit\DigitalPageFlip\Hook\DataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
    = \Kit\DigitalPageFlip\Hook\DataHandlerHook::class;
