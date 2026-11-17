<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Digital Page Flip',
    'description' => 'Converts PDF flyers into browsable online flipbook catalogs using StPageFlip.',
    'category' => 'plugin',
    'author' => 'Kit',
    'state' => 'stable',
    'version' => '2.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.99.99',
            'fluid' => '14.3.0-14.99.99',
            'extbase' => '14.3.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
