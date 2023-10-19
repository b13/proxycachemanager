<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Reverse Proxy - Cache Manager',
    'description' => 'A flexible and generic way to track the pages that are cached by a reverse proxy like nginx HA or a CDN.',
    'category' => 'fe',
    'version' => '4.0.0',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'benni.mack@b13.com',
    'author_company' => 'b13 GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'cacheinfo' => '0.0.0-0.0.0',
        ],
    ],
];
