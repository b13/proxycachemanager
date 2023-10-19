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

use B13\Proxycachemanager\Configuration;
use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Response;

/**
 * Class to handle flushing all caches
 */
class CacheController
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected FrontendInterface $cache, protected Configuration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    /**
     * AJAX endpoint when triggering the call from the cache menu
     */
    public function flushAction(): ResponseInterface
    {
        if ($this->proxyProvider->isActive() && $this->configuration->backendFlushEnabled()) {
            $this->cache->flush();
        }
        $response = new Response();
        return $response;
    }
}
