<?php

declare(strict_types=1);

namespace B13\Proxycachemanager;

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

use B13\Proxycachemanager\Provider\NullProxyProvider;
use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class ProxyConfiguration
{
    protected ?ProxyProviderInterface $proxyProvider = null;

    public function getProxyProvider(): ProxyProviderInterface
    {
        if ($this->proxyProvider === null) {
            $configuration = (GeneralUtility::makeInstance(ExtensionConfiguration::class))->get('proxycachemanager');
            if (!empty($configuration['reverseProxyProvider'])) {
                if (!class_exists($configuration['reverseProxyProvider'])) {
                    throw new \InvalidArgumentException('no such class ' . $configuration['reverseProxyProvider'], 1692699940);
                }
                $this->proxyProvider = GeneralUtility::makeInstance($configuration['reverseProxyProvider']);
                if (!$this->proxyProvider instanceof ProxyProviderInterface) {
                    throw new \InvalidArgumentException($configuration['reverseProxyProvider'] . ' must implement ProxyProviderInterface', 1692699941);
                }
            } else {
                $this->proxyProvider = GeneralUtility::makeInstance(NullProxyProvider::class);
            }
        }
        return $this->proxyProvider;
    }

    public function showBackendModule(): bool
    {
        $configuration = (GeneralUtility::makeInstance(ExtensionConfiguration::class))->get('proxycachemanager');
        return (bool)($configuration['showBackendModule'] ?? false);
    }

    public function backendFlushEnabled(): bool
    {
        $userTsConfig = $GLOBALS['BE_USER']->getTSConfig();
        $isAdmin = $GLOBALS['BE_USER']->isAdmin();
        // Clearing of proxy caches is only shown if explicitly enabled via TSConfig
        // or if BE-User is admin and the TSconfig explicitly disables the possibility for admins.
        // This is useful for big production systems where admins accidentally could slow down the system.
        if (($userTsConfig['options.']['clearCache.']['proxy'] ?? false)
            || ($isAdmin && (bool)($userTsConfig['options.']['clearCache.']['proxy'] ?? true))) {
            return true;
        }
        return false;
    }
}
