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
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * behaves like the Typo3DatabaseBackend and stores frontend URLs of pages in a database
 * when removing or flushing, additionally does a HTTP Request
 * of course "setting" works naturally in am already working reverse proxy environment.
 */
class ReverseProxyCacheBackend extends Typo3DatabaseBackend
{
    use LoggerAwareTrait;
    protected ProxyProviderInterface $reverseProxyProvider;

    public function setReverseProxyProvider(ProxyProviderInterface $reverseProxyProvider)
    {
        $this->reverseProxyProvider = $reverseProxyProvider;
    }

    /**
     * Removes all cache entries of this cache.
     * Also let the proxy provider know to clear everything as well.
     */
    public function flush(): void
    {
        // make the HTTP Purge call
        if ($this->reverseProxyProvider->isActive()) {
            $urls = $this->getAllCachedUrls();
            $this->reverseProxyProvider->flushAllUrls($urls);
        }
        parent::flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag): void
    {
        if ($this->reverseProxyProvider->isActive()) {
            $identifiers = $this->findIdentifiersByTag($tag);
            foreach ($identifiers as $entryIdentifier) {
                $url = $this->resolveCachedUrl($entryIdentifier);
                if ($url !== null) {
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
    public function flushByTags(array $tags): void
    {
        if ($this->reverseProxyProvider->isActive()) {
            $identifiers = [];
            foreach ($tags as $tag) {
                $identifiers = array_merge($identifiers, $this->findIdentifiersByTag($tag));
            }
            $identifiers = array_unique($identifiers);

            $urls = [];
            foreach ($identifiers as $entryIdentifier) {
                $url = $this->resolveCachedUrl($entryIdentifier);
                if ($url !== null) {
                    $urls[] = $url;
                }
            }
            $urls = array_unique($urls);

            if ($urls !== []) {
                $this->reverseProxyProvider->flushCacheForUrls($urls);
            }
        }

        try {
            parent::flushByTags($tags);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to flush ' . count($tags) . ' tags. SQL query limit exceeded. See the list of all tags ' . implode(', ', $tags));
        }
    }

    /**
     * Fetch all URLs in the cache.
     */
    protected function getAllCachedUrls(): array
    {
        $urls = [];
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $rows = $conn->select(['identifier', 'content'], $this->cacheTable)->fetchAllAssociative();
        foreach ($rows as $row) {
            $url = $this->resolveCachedUrl((string)$row['identifier'], $row['content']);
            if ($url !== null) {
                $urls[] = $url;
            }
        }
        return array_values(array_unique($urls));
    }

    /**
     * Resolves the plain page URL stored for a cache entry.
     *
     * Since the backend no longer implements TransientBackendInterface (the v14
     * contract is incompatible with Typo3DatabaseBackend), the configured
     * VariableFrontend serializes — and on v13.4+ HMAC-signs — every entry before
     * it is written to the database. Reads must therefore go through the frontend
     * to obtain the original URL again; a raw column read would only yield the
     * serialized blob.
     *
     * Rows written by older versions (TransientBackendInterface era) still hold the
     * plain URL and fail deserialization, so we fall back to the raw value for those.
     */
    protected function resolveCachedUrl(string $entryIdentifier, ?string $rawContent = null): ?string
    {
        $url = $this->get($entryIdentifier);
        if (is_string($url) && $url !== '') {
            return $url;
        }

        // Legacy fallback: rows from < 5.0.0 stored the plain URL directly.
        if ($rawContent === null) {
            $rawContent = (string)$this->get($entryIdentifier);
        }
        if (preg_match('#^https?://#', $rawContent)) {
            return $rawContent;
        }

        return null;
    }
}
