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

use GuzzleHttp\Client;

/**
 * Uses Cloudflare v4 API with API Tokens (not API Keys).
 * https://api.cloudflare.com/
 *
 * API Tokens are generated from the User Profile 'API Tokens' page https://dash.cloudflare.com/profile/api-tokens.
 * Get your Zone ID via Dash in your Zone on the right-side menu.
 *
 * Please note that the ProxyProvider only works for one Zone (= one domain) currently, however, this would need
 * to be mapped on a per-domain basis.
 */
class CloudflareProxyProvider implements ProxyProviderInterface
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.cloudflare.com/client/v4/zones/{zoneId}/';

    protected $client;

    /**
     * @inheritDoc
     */
    public function setProxyEndpoints($endpoints)
    {
        // not necessary
    }

    /**
     * @inheritDoc
     */
    public function flushCacheForUrl($url)
    {
        if (!$this->isActive()) {
            return;
        }
        $this->getClient()->post('purge_cache', ['files' => [$url]]);
    }

    /**
     * @inheritDoc
     */
    public function flushAllUrls($urls = [])
    {
        if (!$this->isActive()) {
            return;
        }
        if (empty($urls)) {
            $this->getClient()->post('purge_cache', ['purge_everything' => true]);
        } else {
            $this->getClient()->post('purge_cache', ['files' => $urls]);
        }
    }

    /**
     * @inheritDoc
     */
    public function flushCacheForUrls(array $urls)
    {
        if (!$this->isActive()) {
            return;
        }
        $this->getClient()->post('purge_cache', ['files' => $urls]);
    }

    /**
     * @return bool
     */
    protected function isActive()
    {
        return !empty(getenv('CLOUDFLARE_API_TOKEN'));
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = $this->initializeClient(getenv('CLOUDFLARE_ZONE_ID'), getenv('CLOUDFLARE_API_TOKEN'));
        }
        return $this->client;
    }

    /**
     * @param $zoneId
     * @param $apiToken
     *
     * @return Client
     */
    protected function initializeClient(string $zoneId, string $apiToken)
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['base_uri'] = str_replace('{zoneId}', $zoneId, $this->baseUrl);
        $httpOptions['headers']['Content-Type'] = 'application/json';
        $httpOptions['headers']['Authorization'] = 'Bearer ' . $apiToken;
        return new Client($httpOptions);
    }

}
