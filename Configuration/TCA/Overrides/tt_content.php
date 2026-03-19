<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::registerPlugin(
    'DigitalPageFlip',
    'Flipbook',
    'Digital Page Flip - Flipbook',
    'digital-page-flip-flipbook'
);

$pluginSignature = 'digitalpageflip_flipbook';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'pages,recursive';

ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:digital_page_flip/Configuration/FlexForms/Flipbook.xml'
);
