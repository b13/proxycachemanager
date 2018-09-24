<?php

declare(strict_types = 1);

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class containing frontend-related hooks.
 */
class FrontendHook
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Hook that is called when a cacheable page is ready for output
     * calls the proxy cache and stores the pageId, the URL
     * this call costs a little bit of performance but is only called
     * once (!) as the URL is cacheable, next time it is fetched
     * from the reverse proxy directly.
     *
     * @param array                        $parameters
     * @param TypoScriptFrontendController $parentObject
     */
    public function addCacheableUrlToProxyCache($parameters, TypoScriptFrontendController $parentObject)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']) {
             return;
        }
        try {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_proxy');
            $pageUid = $parentObject->id;

            // cache the page URL that was called
            $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
            $cache->set(md5($url), $url, ['pageId_' . $pageUid]);
            $this->getLogger()->info(
                'Marking page "%s" (uid %s) as cached.',
                [$url, $pageUid]
            );

            foreach ($parentObject->imagesOnPage as $imageUrl) {
                // Only cache local files
                $hasSchema = parse_url($imageUrl, PHP_URL_SCHEME);
                if (!empty($hasSchema)) {
                    continue;
                }
                // If it's not from an extension, clear the cache
                // Extension files are not changed, only during deployment, where we expect that all caches
                // Are flushed
                if (strpos($imageUrl, 'typo3conf/ext/') !== false) {
                    continue;
                }
                // processed images are not cached either
                if (strpos($imageUrl, '/_processed_') !== false) {
                    continue;
                }
                $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $imageUrl;
                $tags = ['pageId_' . $pageUid];
                $cache->set(md5($url), $url, $tags);
                $this->getLogger()->info(
                    'Marking image "%s" (on page %s) as cached.',
                    [$url, $pageUid]
                );
            }
        } catch (NoSuchCacheException $e) {
            // No cache, nothing to do
        }
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        if (!$this->logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger('b13.proxy.cache.populate');
        }

        return $this->logger;
    }
}
