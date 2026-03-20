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
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
    = \Kit\DigitalPageFlip\Hook\DataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
    = \Kit\DigitalPageFlip\Hook\DataHandlerHook::class;
