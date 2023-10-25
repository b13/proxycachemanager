<?php

defined('TYPO3') or die();

use B13\Proxycachemanager\Controller\ManagementController;
use B13\Proxycachemanager\ProxyConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if ((GeneralUtility::makeInstance(ProxyConfiguration::class))->showBackendModule()) {
    return [
        'site_proxycachemanager' => [
            'parent' => 'site',
            'access' => 'user',
            'path' => '/module/site/ProxycachemanagerCdnCache',
            'labels' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang_module_cache.xlf',
            'extensionName' => 'Proxycachemanager',
            'icon' => 'EXT:proxycachemanager/Resources/Public/Icons/CacheModule.png',
            'inheritNavigationComponentFromMainModule' => false,
            'controllerActions' => [
                ManagementController::class => ['index', 'clearTag', 'purgeUrl'],
            ],
        ],
    ];
}
