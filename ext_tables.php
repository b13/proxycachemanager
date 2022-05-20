<?php

defined('TYPO3_MODE') or die('Access denied!');

$proxyCacheManagerConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('proxycachemanager');
if ($proxyCacheManagerConfiguration['showBackendModule'] ?? false) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'B13.proxycachemanager',
        'site',
        'cdn_cache',
        'bottom',
        ['Management' => 'index,clearTag,purgeUrl'],
        [
            'access' => 'user,group',
            'icon' => 'EXT:proxycachemanager/Resources/Public/Icons/CacheModule.png',
            'labels' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang_module_cache.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false,
        ]
    );
}
