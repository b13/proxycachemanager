<?php

declare(strict_types=1);

namespace B13\Proxycachemanager\ResourceStorageOperations;

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

use B13\Proxycachemanager\Configuration;
use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

class CacheService implements SingletonInterface
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected SiteFinder $siteFinder, Configuration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    public function flushCachesForFile(FileInterface $file): void
    {
        $this->flushCaches([$file->getPublicUrl()]);
    }

    public function flushCachesForFolder(Folder $folder): void
    {
        $this->flushCaches($this->getPublicUrlsInFolder($folder));
    }

    protected function getPublicUrlsInFolder(Folder $folder): array
    {
        $urls = [];
        $files = $folder->getFiles();
        foreach ($files as $file) {
            $urls[] = $file->getPublicUrl();
        }
        return $urls;
    }

    protected function flushCaches(array $publicUrls): void
    {
        if (!$this->proxyProvider->isActive()) {
            return;
        }
        $urls = [];
        $sites = $this->siteFinder->getAllSites();
        foreach ($sites as $site) {
            foreach ($publicUrls as $publicUrl) {
                $urls[] = rtrim($site->getBase()->__toString(), '/') . '/' . ltrim($publicUrl, '/');
            }
        }
        if (!empty($urls)) {
            $this->proxyProvider->flushCacheForUrls($urls);
        }
    }
}
