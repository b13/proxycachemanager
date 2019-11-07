<?php
defined('TYPO3_MODE') or die();

$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('proxycachemanager');

if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']['tx_proxycachemanager'] =
    \B13\Proxycachemanager\Hook\FrontendHook::class;

// Hook for adding an additional cache clearing button
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['tx_proxycachemanager'] =
    \B13\Proxycachemanager\Controller\CacheController::class;
