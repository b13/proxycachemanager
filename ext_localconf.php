<?php

if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// register the cache that stores cacheable frontend URLs and their page IDs
// but only if a reverse Proxy IP was set
if (!empty($TYPO3_CONF_VARS['SYS']['reverseProxyIP'])) {
	$TYPO3_CONF_VARS['EXTCONF']['tx_proxycachemanager'] = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['proxycachemanager']);

	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_proxy'] = array(
		'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
		'backend' => 'B13\\Proxycachemanager\\Cache\\Backend\\ReverseProxyCacheBackend',
		'options' => array(
			'defaultLifetime' => 0,	// @todo: should be not "infinite" but rather set to whatever the proxy settings are
			'reverseProxyProvider'  => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_proxycachemanager']['reverseProxyProvider'] ?: 'B13\\Proxycachemanager\\Provider\\CurlHttpProxyProvider',
			'reverseProxyEndpoints' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_proxycachemanager']['reverseProxyEndpoints'],
		),
		// setting the pages group makes sure that when the page cache is cleared, that this cache is cleared as well
		'groups' => array('pages', 'proxy')
	);

	// hook for adding any cacheable frontend URL to our proxy cache
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']['tx_proxycachemanager'] = 'B13\\Proxycachemanager\\Hook\\FrontendHook->addCacheableUrlToProxyCache';
}
