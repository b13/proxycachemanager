<?php
defined('TYPO3_MODE') or die();

if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] ?? null)) {
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('proxycachemanager');
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \B13\Proxycachemanager\Cache\Backend\ReverseProxyCacheBackend::class,
        'options' => [
            'defaultLifetime' => 0, // @todo: should be not "infinite" but rather set to whatever the proxy settings are
            'reverseProxyProvider' => $configuration['reverseProxyProvider'] ?? \B13\Proxycachemanager\Provider\CurlHttpProxyProvider::class,
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

if ((\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class))->getMajorVersion() < 10) {
    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFileRename,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'fileRename'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFileMove,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'fileMove'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFileDelete,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'fileDelete'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFileReplace,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'fileReplace'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFolderDelete,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'folderDelete'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFolderRename,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'folderRename'
    );
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreFolderMove,
        \B13\Proxycachemanager\ResourceStorageOperations\ResourceStorageSlot::class,
        'folderMove'
    );
}

