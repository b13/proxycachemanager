<?php

declare(strict_types=1);

namespace B13\Proxycachemanager\Listener;

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
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;

class AfterCacheIsPersisted
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected FrontendInterface $cache, Configuration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    public function __invoke(AfterCachedPageIsPersistedEvent $event): void
    {
        file_put_contents('/tmp/after.txt', '1');
        if (!$this->proxyProvider->isActive()) {
            return;
        }
        $cacheTags = $event->getController()->getPageCacheTags();
        $url = (string)$event->getRequest()->getUri();
        $this->cache->set(md5($url), $url, $cacheTags, $event->getCacheLifetime());
    }
}
