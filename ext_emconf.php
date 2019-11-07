<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Reverse Proxy - Cache Manager',
    'description' => 'A flexible and generic way to track the pages that are cached by a reverse proxy like nginx or varnish.',
    'category' => 'fe',
    'version' => '3.0.0',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'benni.mack@b13.com',
    'author_company' => 'b13 GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.5-10.9.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'cacheinfo' => '0.0.0-0.0.0',
        ],
    ],
];
