<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_page',
        'label' => 'page_number',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY sorting',
        'iconfile' => 'EXT:digital_page_flip/Resources/Public/Icons/Extension.svg',
        'hideTable' => true,
    ],
    'types' => [
        '1' => [
            'showitem' => 'page_number, image, image_fallback, sorting',
        ],
    ],
    'columns' => [
        'flipbook' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'page_number' => [
            'exclude' => false,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_page.page_number',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'readOnly' => true,
            ],
        ],
        'image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_page.image',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'webp,png,jpg',
            ],
        ],
        'image_fallback' => [
            'exclude' => true,
            'label' => 'LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf:tx_digitalpageflip_domain_model_page.image_fallback',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'png,jpg',
            ],
        ],
        'sorting' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
