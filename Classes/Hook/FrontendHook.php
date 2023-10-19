<?php

declare(strict_types=1);

namespace B13\Proxycachemanager\Hook;

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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class containing frontend-related hooks.
 */
class FrontendHook
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected FrontendInterface $cache, Configuration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    /**
     * Hook that is called when a cacheable page was just added to the cache system
     * calls the proxy cache and stores the pageId, the URL
     * this call costs a little bit of performance but is only called
     * once (!) as the URL is cacheable, next time it is fetched
     * from the reverse proxy directly.
     *
     * @param TypoScriptFrontendController $frontendController
     * @param $timeOutTime
     */
    public function insertPageIncache(TypoScriptFrontendController $frontendController, $timeOutTime)
    {
        if (!$this->proxyProvider->isActive() || !$this->proxyProvider->shouldRequestBeMarkedAsCached($this->getRequest())) {
            return;
        }
        // cache the page URL that was called
        $url = (string)$this->getRequest()->getUri();
        $this->cache->set(md5($url), $url, $frontendController->getPageCacheTags(), $timeOutTime);
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
