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
    protected ProxyProviderInterface $reverseProxyProvider;

    public function setReverseProxyProvider(ProxyProviderInterface $reverseProxyProvider)
    {
        $this->reverseProxyProvider = $reverseProxyProvider;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * Please note: As remove() is called when using set() in the parent method,
     * it would also flush the cache when the FE is accessed, resulting in a lot of
     * cache entries. This should be avoided, for this reason, we do not call the
     * provider anymore. Ideally we should not invalidate but rather push this actively
     * to the proxy in the future.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     *
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        return parent::remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     * Also let the proxy provider know to clear everything as well.
     */
    public function flush()
    {
        parent::flush();

        // make the HTTP Purge call
        if ($this->reverseProxyProvider->isActive()) {
            $urls = $this->getAllCachedUrls();
            $this->reverseProxyProvider->flushAllUrls($urls);
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        if ($this->reverseProxyProvider->isActive()) {
            $identifiers = $this->findIdentifiersByTag($tag);
            foreach ($identifiers as $entryIdentifier) {
                $url = $this->get($entryIdentifier);
                if ($url) {
                    $this->reverseProxyProvider->flushCacheForUrls([$url]);
                }
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
        if ($this->reverseProxyProvider->isActive()) {
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
        }

        parent::flushByTags($tags);
    }

    /**
     * Fetch all URLs in the cache.
     */
    protected function getAllCachedUrls(): array
    {
        $urls = [];
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $stmt = $conn->select(['content'], $this->cacheTable);
        while ($url = $stmt->fetchOne()) {
            $urls[] = $url;
        }
        return $urls;
    }
}
