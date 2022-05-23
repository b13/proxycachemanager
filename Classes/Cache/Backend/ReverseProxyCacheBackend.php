<?php

declare(strict_types=1);
namespace B13\Proxycachemanager\Cache\Backend;

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
use TYPO3\CMS\Core\Cache\Backend\TransientBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * behaves like the Typo3DatabaseBackend and stores frontend URLs of pages in a database
 * when removing or flushing, additionally does a HTTP Request
 * of course "setting" works naturally in am already working reverse proxy environment.
 */
class ReverseProxyCacheBackend extends Typo3DatabaseBackend implements TransientBackendInterface
{
    /**
     * @var ProxyProviderInterface
     */
    protected $reverseProxyProvider;

    /**
     * set from the AbstractCacheBackend when the object is instantiated.
     *
     * @param string $className
     */
    public function setReverseProxyProvider(string $className)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('Invalid cache proxy provider for Reverse Proxy Cache', 1231267264);
        }
        try {
            $this->reverseProxyProvider = GeneralUtility::makeInstance($className);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Invalid cache proxy provider class for Reverse Proxy Cache - Class "' . $className . '" not found.',
                1231267264
            );
        }
    }

    /**
     * set the hostnames of the reverse proxies
     * set from the AbstractCacheBackend when the object is instantiated.
     *
     * @param string $endpoints
     */
    public function setReverseProxyEndpoints($endpoints = null)
    {
        // assume it the reverse proxy is on the same host
        if (empty($endpoints)) {
            $endpoints = GeneralUtility::getIndpEnv('HTTP_HOST');
        }
        $endpoints = GeneralUtility::trimExplode(',', $endpoints);
        $this->reverseProxyProvider->setProxyEndpoints($endpoints);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     *
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        // call the provider to forget this URL
        $url = $this->get($entryIdentifier);
        if ($url) {
            $this->reverseProxyProvider->flushCacheForUrl($url);
        }

        return parent::remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     * Also let the proxy provider know to clear everything as well.
     */
    public function flush()
    {
        $urls = $this->getAllCachedUrls();
        parent::flush();

        // make the HTTP Purge call
        $this->reverseProxyProvider->flushAllUrls($urls);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $entryIdentifier) {
            $url = $this->get($entryIdentifier);
            if ($url) {
                $this->reverseProxyProvider->flushCacheForUrl($url);
            }
        }

        parent::flushByTag($tag);
    }

    /**
     * Removes all entries tagged by any of the specified tags.
     *
     * @param string[] $tags
     */
    public function flushByTags(array $tags)
    {
        $identifiers = [];
        foreach ($tags as $tag) {
            $identifiers = array_merge($identifiers, $this->findIdentifiersByTag($tag));
        }
        $identifiers = array_unique($identifiers);

        $urls = [];
        foreach ($identifiers as $entryIdentifier) {
            $urls[] = $this->get($entryIdentifier);
        }
        $urls = array_unique($urls);

        $this->reverseProxyProvider->flushCacheForUrls($urls);

        parent::flushByTags($tags);
    }

    /**
     * Fetch all URLs in the cache.
     */
    public function getAllCachedUrls()
    {
        $urls = [];
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $stmt = $conn->select(['content'], $this->cacheTable);
        while ($url = $stmt->fetchColumn(0)) {
            $urls[] = $url;
        }
        return $urls;
    }
}
