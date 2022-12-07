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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class containing frontend-related hooks.
 */
class FrontendHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Hook that is called when a cacheable page was just added to the cache system
     * calls the proxy cache and stores the pageId, the URL
     * this call costs a little bit of performance but is only called
     * once (!) as the URL is cacheable, next time it is fetched
     * from the reverse proxy directly.
     *
     * @param TypoScriptFrontendController $parentObject
     * @param $timeOutTime
     */
    public function insertPageIncache(TypoScriptFrontendController $parentObject, $timeOutTime)
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
            return;
        }
        try {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_proxy');
            $pageUid = $parentObject->id;

            // cache the page URL that was called
            $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
            $cache->set(md5($url), $url, $parentObject->getPageCacheTags(), $timeOutTime);
            $this->logger->info(
                'Marking page "%s" (uid %s) as cached.',
                [$url, $pageUid]
            );
        } catch (NoSuchCacheException $e) {
            // No cache, nothing to do
        }
    }
}
