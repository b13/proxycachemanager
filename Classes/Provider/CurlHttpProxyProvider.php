<?php
declare(strict_types = 1);
namespace B13\Proxycachemanager\Provider;

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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Works with a queue directly with CURL.
 * Using guzzle here is the next step.
 */
class CurlHttpProxyProvider implements ProxyProviderInterface, SingletonInterface
{
    /**
     * a queue so that within one request, the flush request is only done once (see executeCacheFlush()).
     *
     * @var array
     */
    protected $queue = [];

    /**
     * a list of URLs of the proxy endpoints to be called.
     *
     * @var array
     */
    protected $proxyEndpoints = [];

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function setProxyEndpoints($endpoints)
    {
        $this->proxyEndpoints = $endpoints;
    }

    /**
     * {@inheritdoc}
     */
    public function flushCacheForUrl($url)
    {
        $this->queue[] = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function flushCacheForUrls(array $urls)
    {
        $this->queue = $urls;
        $this->executeCacheFlush();
    }

    /**
     * {@inheritdoc}
     */
    public function flushAllUrls($urls = [])
    {
        $this->flushCacheForUrls($urls);
    }

    /**
     * calls the reverse proxy via a URL cache.
     */
    protected function executeCacheFlush()
    {
        if (!empty($this->queue)) {
            $this->queue = array_unique($this->queue);

            $curlQueueHandler = curl_multi_init();
            $curlHandles = [];

            foreach ($this->queue as $urlToFlush) {
                foreach ($this->proxyEndpoints as $proxyEndpoint) {
                    $curlHandle = $this->getCurlHandleForPurgeHttpRequest($urlToFlush, $proxyEndpoint);
                    $curlHandles[] = $curlHandle;
                    curl_multi_add_handle($curlQueueHandler, $curlHandle);
                }
            }

            $active = null;
            do {
                $multiExecResult = curl_multi_exec($curlQueueHandler, $active);
            } while (CURLM_CALL_MULTI_PERFORM == $multiExecResult);

            while ($active && CURLM_OK == $multiExecResult) {
                if (curl_multi_select($curlQueueHandler) != -1) {
                    do {
                        $multiExecResult = curl_multi_exec($curlQueueHandler, $active);
                    } while (CURLM_CALL_MULTI_PERFORM == $multiExecResult);
                }
            }

            foreach ($curlHandles as $curlHandle) {
                curl_multi_remove_handle($curlQueueHandler, $curlHandle);
            }

            curl_multi_close($curlQueueHandler);
            // and empty the URL queue again
            $this->queue = [];
        }
    }

    /**
     * Instantiates a curl handle in order to call.
     *
     * @param string $urlToPurge  The URL that should be cleared
     * @param string $endpointUrl the URL of the proxy server that deals with the purging
     *
     * @return resource
     */
    protected function getCurlHandleForPurgeHttpRequest($urlToPurge, $endpointUrl)
    {
        $urlParts = parse_url($urlToPurge);
        $finalEndpointUrl = str_replace(
            ['{scheme}', '{host}', '{port}', '{user}', '{pass}', '{path}', '{query}', '{fragment}', '{url}'],
            [
                $urlParts['scheme'],
                $urlParts['host'],
                $urlParts['port'],
                $urlParts['user'],
                $urlParts['pass'],
                trim($urlParts['path'], '/'),
                $urlParts['query'],
                $urlParts['fragment'],
                $urlToPurge,
            ],
            $endpointUrl
        );

        $this->getLogger()->info(
            'Purging "%s" on endpoint "%s"',
            [$finalEndpointUrl, $urlToPurge]
        );

        $curlHandle = curl_init($finalEndpointUrl);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PURGE');
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);

        return $curlHandle;
    }

    /**
     * call the execution of the HTTP requests (queue worker).
     */
    public function __destruct()
    {
        $this->executeCacheFlush();
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }
}
