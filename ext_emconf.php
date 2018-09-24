<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Reverse Proxy - Cache Manager',
    'description' => 'A flexible and generic way to track the pages that are cached by a reverse proxy like nginx or varnish.',
    'category' => 'fe',
    'version' => '2.0.3',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'benni@typo3.org',
    'author_company' => 'b:dreizehn GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '7.0.0-9.9.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'cacheinfo' => '0.0.0-0.0.0',
        ],
    ],
    '_md5_values_when_last_written' => '',
];
