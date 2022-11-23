<?php

declare(strict_types=1);
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

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class FastlyProxyProvider implements ProxyProviderInterface
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.fastly.com/service/{serviceId}/';

    /**
     * {@inheritdoc}
     */
    public function setProxyEndpoints($endpoints)
    {
        // not necessary
    }

    /**
     * {@inheritdoc}
     */
    public function flushCacheForUrl($url)
    {
        if (!$this->isActive()) {
            return;
        }
        $this->getClient()->request('PURGE', $url, ['verify' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function flushCacheForUrls(array $urls)
    {
        foreach ($urls as $url) {
            $this->flushCacheForUrl($url);
        }
    }

    /**
     * Flushes the whole proxy cache.
     *
     * @param array $urls
     */
    public function flushAllUrls($urls = [])
    {
        if (!$this->isActive()) {
            return;
        }
        $this->getClient()->post('purge_all');
    }

    /**
     * @return bool
     */
    protected function isActive()
    {
        return !empty(getenv('FASTLY_SERVICE_ID'));
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->initializeClient(getenv('FASTLY_SERVICE_ID'), getenv('FASTLY_API_TOKEN'));
    }

    /**
     * @param $serviceId
     * @param $apiToken
     *
     * @return Client
     */
    protected function initializeClient($serviceId, $apiToken)
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        if (isset($httpOptions['handler'])) {
            if (is_array($httpOptions['handler'] && !empty($httpOptions['handler']))) {
                $stack = HandlerStack::create();
                foreach ($httpOptions['handler'] as $handler) {
                    $stack->push($handler);
                }
                $httpOptions['handler'] = $stack;
            }
            else {
                unset($httpOptions['handler']);
            }
        }
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];
        $httpOptions['base_uri'] = str_replace('{serviceId}', $serviceId, $this->baseUrl);
        $httpOptions['headers']['Fastly-Key'] = $apiToken;

        return new Client($httpOptions);
    }
}
