<?php

defined('TYPO3') or die();

use B13\Proxycachemanager\ProxyConfiguration;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (
    (GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12 &&
    (GeneralUtility::makeInstance(ProxyConfiguration::class))->showBackendModule()
) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'proxycachemanager',
        'site',
        'cdn_cache',
        'bottom',
        [\B13\Proxycachemanager\Controller\ManagementController::class => 'index,clearTag,purgeUrl'],
        [
            'access' => 'user,group',
            'icon' => 'EXT:proxycachemanager/Resources/Public/Icons/CacheModule.png',
            'labels' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang_module_cache.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false,
        ]
    );
}
