<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Register as dedicated Content Element (not a generic plugin)
ExtensionUtility::registerPlugin(
    'DigitalPageFlip',
    'Flipbook',
    'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tt_content.CType.digitalpageflip_flipbook',
    'digital-page-flip-flipbook',
    'plugins',
);

// Define the custom flipbook selection field on tt_content
$GLOBALS['TCA']['tt_content']['columns']['tx_digitalpageflip_flipbook'] = [
    'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tt_content.tx_digitalpageflip_flipbook',
    'config' => [
        'type' => 'group',
        'allowed' => 'tx_digitalpageflip_domain_model_flipbook',
        'maxitems' => 1,
        'size' => 1,
    ],
];

// Configure showitem for the new CType
$GLOBALS['TCA']['tt_content']['types']['digitalpageflip_flipbook'] = [
    'showitem' => implode(',', [
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
        '--palette--;;general',
        'header',
        'tx_digitalpageflip_flipbook',
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
        '--palette--;;hidden',
        '--palette--;;access',
    ]),
];
