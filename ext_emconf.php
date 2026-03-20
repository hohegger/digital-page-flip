<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Digital Page Flip',
    'description' => 'Converts PDF flyers into browsable online flipbook catalogs using StPageFlip.',
    'category' => 'plugin',
    'author' => 'Kit',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
