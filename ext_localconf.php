<?php

defined('TYPO3') or die();

use B13\Proxycachemanager\Provider\NullProxyProvider;
use B13\Proxycachemanager\ProxyConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] ?? null)) {
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('proxycachemanager');
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \B13\Proxycachemanager\Cache\Backend\ReverseProxyCacheBackend::class,
        'options' => [
            'defaultLifetime' => 0, // @todo: should be not "infinite" but rather set to whatever the proxy settings are
            'reverseProxyProvider' => $configuration['reverseProxyProvider'] ?? NullProxyProvider::class,
        ],
        // setting the pages group makes sure that when the page cache is cleared, that this cache is cleared as well
        'groups' => ['pages', 'proxy'],
    ];
}

// Hook for adding any cacheable frontend URL to our proxy cache
// v11 only
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']['tx_proxycachemanager'] =
    \B13\Proxycachemanager\Hook\FrontendHook::class;
