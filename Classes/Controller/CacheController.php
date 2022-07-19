<?php

declare(strict_types=1);
namespace B13\Proxycachemanager\Controller;

/*
 * This file is part of the b13 TYPO3 extensions family.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to handle flushing all caches
 */
class CacheController implements ClearCacheActionsHookInterface
{

    /**
     * Modifies CacheMenuItems array and adds a "flush CDN caches"
     *
     * @param array $cacheActions Array of CacheMenuItems
     * @param array $optionValues Array of AccessConfigurations-identifiers (typically used by userTS with options.clearCache.identifier)
     */
    public function manipulateCacheActions(&$cacheActions, &$optionValues)
    {
        if ($this->shouldShowCacheFlushButton()) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $optionValues[] = 'clearProxyCache';
            $item = [
                'id' => 'clearProxyCache',
                'title' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:menuitem.title',
                'description' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:menuitem.description',
                'href' => $uriBuilder->buildUriFromRoute('ajax_proxy_flushcaches'),
                'iconIdentifier' => 'actions-system-cache-clear-impact-medium',
            ];
            // Move "our" item on second place
            $firstItem = array_shift($cacheActions);
            array_unshift($cacheActions, $item);
            array_unshift($cacheActions, $firstItem);
        }
    }

    public function shouldShowCacheFlushButton()
    {
        $providerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy']['options']['reverseProxyProvider'] ?? null;
        if (empty($providerClassName)) {
            return false;
        }
        $userTsConfig = $GLOBALS['BE_USER']->getTSConfig();
        $isAdmin = $GLOBALS['BE_USER']->isAdmin();
        // Clearing of proxy caches is only shown if explicitly enabled via TSConfig
        // or if BE-User is admin and the TSconfig explicitly disables the possibility for admins.
        // This is useful for big production systems where admins accidentally could slow down the system.
        if (($userTsConfig['options.']['clearCache.']['proxy'] ?? false)
            || ($isAdmin && (bool)($userTsConfig['options.']['clearCache.']['proxy'] ?? true)))
        {
            return true;
        }
        return false;
    }

    /**
     * AJAX endpoint when triggering the call from the cache menu
     * @return Response
     */
    public function flushAction()
    {
        $providerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_proxy']['options']['reverseProxyProvider'] ?? null;
        if ($providerClassName) {
            /** @var ProxyProviderInterface $cacheProvider */
            $proxyProvider = GeneralUtility::makeInstance($providerClassName);
            $proxyProvider->flushAllUrls();
            $content = [
                'success' => true,
                'message' => $this->getLanguageService()->sL('LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:purge.success'),
            ];
        } else {
            $content = [
                'status' => true,
                'message' => $this->getLanguageService()->sL('LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:purge.failure'),
            ];
        }
        // we cannot add our own message unfortunately
        $response = new Response();
        //$response->getBody()->write(json_encode($content));
        return $response;
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
