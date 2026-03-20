<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,description',
        'iconfile' => 'EXT:digital_page_flip/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, description, pdf_file,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden, starttime, endtime,
                --div--;Pages,
                    pages, page_count, conversion_status,
            ',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim',
            ],
        ],
        'pdf_file' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.pdf_file',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'pdf',
            ],
        ],
        'pages' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.pages',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_digitalpageflip_domain_model_page',
                'foreign_field' => 'flipbook',
                'foreign_sortby' => 'sorting',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => false,
                    'showPossibleLocalizationRecords' => false,
                    'showAllLocalizationLink' => false,
                    'enabledControls' => [
                        'info' => true,
                        'new' => false,
                        'dragdrop' => false,
                        'sort' => false,
                        'hide' => false,
                        'delete' => false,
                        'localize' => false,
                    ],
                ],
            ],
        ],
        'page_count' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.page_count',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'readOnly' => true,
            ],
        ],
        'conversion_status' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.conversion_status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'items' => [
                    ['label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.conversion_status.pending', 'value' => 0],
                    ['label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.conversion_status.processing', 'value' => 1],
                    ['label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.conversion_status.completed', 'value' => 2],
                    ['label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_flipbook.conversion_status.error', 'value' => 3],
                ],
            ],
        ],
    ],
];
