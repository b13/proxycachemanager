<?php

defined('TYPO3_MODE') or die();

(function($_EXTKEY) {
    $configuration = version_compare(TYPO3_branch, '9.5', '<')
        ? unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['proxycachemanager'])
        : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get($_EXTKEY)
    ;

    if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class,
            'backend' => \B13\Proxycachemanager\Cache\Backend\ReverseProxyCacheBackend::class,
            'options' => [
                'defaultLifetime' => 0, // @todo: should be not "infinite" but rather set to whatever the proxy settings are
                'reverseProxyProvider' => $configuration['reverseProxyProvider'] ?: \B13\Proxycachemanager\Provider\CurlHttpProxyProvider::class,
                'reverseProxyEndpoints' => $configuration['reverseProxyEndpoints'],
            ],
            // setting the pages group makes sure that when the page cache is cleared, that this cache is cleared as well
            'groups' => ['pages', 'proxy'],
        ];
    }
    // Hook for adding any cacheable frontend URL to our proxy cache
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']['tx_proxycachemanager'] =
        \B13\Proxycachemanager\Hook\FrontendHook::class . '->addCacheableUrlToProxyCache';

    // Hook for adding an additional cache clearing button
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['tx_proxycachemanager'] =
        \B13\Proxycachemanager\Controller\CacheController::class;

    // XCLASS to add a getter for pageCacheTags in TSFE
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class] = [
        'className' => \B13\Proxycachemanager\Controller\TypoScriptFrontendController::class
    ];
})($_EXTKEY);
