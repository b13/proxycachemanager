<?php

/**
 * Definitions for routes provided by EXT:proxycachemanager
 */
return [
    'proxy_flushcaches' => [
        'path' => '/proxy/purge',
        'target' => \B13\Proxycachemanager\Controller\CacheController::class . '::flushAction'
    ],
];
