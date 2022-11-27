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
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Uses Cloudflare v4 API with API Tokens (not API Keys).
 * https://api.cloudflare.com/
 *
 * API Tokens are generated from the User Profile 'API Tokens' page https://dash.cloudflare.com/profile/api-tokens.
 * Get your Zone ID via Dash in your Zone on the right-side menu.
 *
 * Ensure to set the environment variable CLOUDFLARE_API_TOKEN.
 *
 * Please note that the ProxyProvider needs additional configuration for each zone in
 *
 * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['proxycachemanager']['cloudflare']['zones'] = [
 *  'example.com' => 'ZONE_ID'
 * ];
 */
class CloudflareProxyProvider implements ProxyProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    protected $baseUrl = 'https://api.cloudflare.com/client/v4/zones/{zoneId}/';

    /**
     * @var Client[]
     */
    protected $clients;

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
        $groupedUrls = $this->groupUrlsByAllowedZones([$url]);
        foreach ($groupedUrls as $zoneId => $urls) {
            if (empty($urls)) {
                continue;
            }
            $data = ['json' => ['files' => array_values($urls)]];
            try {
                $this->getClient($zoneId)->post('purge_cache', $data);
            } catch (TransferException $e) {
                $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                    'urls' => $urls,
                    'zone' => $zoneId,
                    'exception' => $e,
                ]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function flushAllUrls($urls = [])
    {
        if (!$this->isActive()) {
            return;
        }
        foreach ($this->getZones() as $domain => $zoneId) {
            try {
                $this->getClient($zoneId)->post('purge_cache', ['json' => ['purge_everything' => true]]);
            } catch (TransferException $e) {
                $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                    'urls' => $urls,
                    'zone' => $zoneId,
                    'exception' => $e,
                ]);
            }
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

        $groupedUrls = $this->groupUrlsByAllowedZones($urls);
        foreach ($groupedUrls as $zoneId => $urls) {
            $this->purgeInChunks($zoneId, $urls);
        }
    }

    /**
     * Cloudflare only allows to purge 30 urls per request, so we chunk this.
     *
     * @param string $zoneId
     * @param array $urls
     * @param int $chunkSize
     */
    protected function purgeInChunks(string $zoneId, array $urls, int $chunkSize = 30): void
    {
        if (empty($urls)) {
            return;
        }
        $client = $this->getClient($zoneId);
        $urlGroups = array_chunk($urls, $chunkSize);
        foreach ($urlGroups as $urlGroup) {
            if (!empty($urlGroup)) {
                try {
                    $client->post('purge_cache', ['json' => ['files' => array_values($urlGroup)]]);
                } catch (TransferException $e) {
                    $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                        'urls' => $urls,
                        'zone' => $zoneId,
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function isActive()
    {
        return !empty(getenv('CLOUDFLARE_API_TOKEN'));
    }

    /**
     * @param string $zoneId
     * @return Client
     */
    protected function getClient(string $zoneId): Client
    {
        if (!isset($this->clients[$zoneId])) {
            $this->clients[$zoneId] = $this->initializeClient($zoneId, getenv('CLOUDFLARE_API_TOKEN'));
        }
        return $this->clients[$zoneId];
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
        if (isset($httpOptions['handler'])) {
            if (is_array($httpOptions['handler'] && !empty($httpOptions['handler']))) {
                $stack = HandlerStack::create();
                foreach ($httpOptions['handler'] as $handler) {
                    $stack->push($handler);
                }
                $httpOptions['handler'] = $stack;
            } else {
                unset($httpOptions['handler']);
            }
        }
        $httpOptions['base_uri'] = str_replace('{zoneId}', $zoneId, $this->baseUrl);
        $httpOptions['headers']['Content-Type'] = 'application/json';
        $httpOptions['headers']['Authorization'] = 'Bearer ' . $apiToken;
        return new Client($httpOptions);
    }

    /**
     * A URL could look like www-intranet.example.com but the zone would be example.com in this case,
     * this is filtered and grouped.
     *
     * @param array $urls
     * @return array
     */
    protected function groupUrlsByAllowedZones(array $urls): array
    {
        $groupedUrls = [];
        $availableZones = $this->getZones();
        foreach ($availableZones as $domain => $zoneId) {
            $groupedUrls[$zoneId] = array_filter($urls, function ($url) use ($domain) {
                $domainOfUrl = parse_url($url, PHP_URL_HOST);
                if (stripos('.' . $domainOfUrl, '.' . $domain) !== false) {
                    return true;
                }
                return false;
            });
        }
        return $groupedUrls;
    }

    protected function getZones(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['proxycachemanager']['cloudflare']['zones'] ?? [];
    }
}
