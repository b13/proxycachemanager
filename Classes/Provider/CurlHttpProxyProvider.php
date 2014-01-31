<?php

namespace B13\Proxycachemanager\Provider;

/***************************************************************
 *  Copyright notice - MIT License (MIT)
 *
 *  (c) 2014 Benjamin Mack <benni@typo3.org>
 *  All rights reserved
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 ***************************************************************/

/**
 * behaves like the Typo3DatabaseBackend and stores frontend URLs of pages in a database
 * when removing or flushing, additionally does a HTTP Request
 * of course "setting" works naturally in am already working reverse proxy environment
 *
 * @package B13\Proxycachemanager\Provider
 */
class CurlHttpProxyProvider implements ProxyProviderInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * a queue so that within one request, the flush request is only done once (see executeCacheFlush())
	 *
	 * @var array
	 */
	protected $queue = array();

	/**
	 * a list of URLs of the proxy endpoints to be called
	 * @var array
	 */
	protected $proxyEndpoints = array();

	/**
	 * sets the proxy endpoints
	 * @param $endpoints
	 */
	public function setProxyEndpoints($endpoints) {
		$this->proxyEndpoints = $endpoints;
	}

	/**
	 * flushes the proxy cache for a single URL
	 */
	public function flushCacheForUrl($url) {
		$this->queue[] = $url;
	}

	/**
	 * flushes the whole proxy cache (directlry)
	 */
	public function flushAllUrls() {
		$this->queue = array('.*');
		$this->executeCacheFlush();
	}

	/**
	 * calls the reverse proxy via a URL cache
	 * @return void
	 */
	protected function executeCacheFlush() {
		if (count($this->queue) > 0) {
			$this->queue = array_unique($this->queue);

			$curlQueueHandler = curl_multi_init();
			$curlHandles = array();

			foreach ($this->queue as $urlToFlush) {
				foreach ($this->proxyEndpoints as $proxyEndpoint) {
					$curlHandle = $this->getCurlHandleForPurgeHttpRequest($urlToFlush, $proxyEndpoint);
					$curlHandles[] = $curlHandle;
					curl_multi_add_handle($curlQueueHandler, $curlHandle);
				}
			}

			$active = NULL;
			do {
				$multiExecResult = curl_multi_exec($curlQueueHandler, $active);
			} while ($multiExecResult == CURLM_CALL_MULTI_PERFORM);

			while ($active && $multiExecResult == CURLM_OK) {
				if (curl_multi_select($curlQueueHandler) != -1) {
					do {
						$multiExecResult = curl_multi_exec($curlQueueHandler, $active);
					} while ($multiExecResult == CURLM_CALL_MULTI_PERFORM);
				}
			}

			foreach ($curlHandles as $curlHandle) {
				curl_multi_remove_handle($curlQueueHandler, $curlHandle);
			}

			curl_multi_close($curlQueueHandler);
			// and empty the URL queue again
			$this->queue = array();
		}
	}

	/**
	 * instantiates a curl handle in order to call
	 * @param string $urlToPurge The URL that should be cleared
	 * @param string $endpointUrl the URL of the proxy server that deals with the purging
	 * @return resource
	 */
	protected function getCurlHandleForPurgeHttpRequest($urlToPurge, $endpointUrl) {
		$urlParts = parse_url($urlToPurge);
		$finalEndpointUrl = str_replace(
			array('{scheme}', '{host}', '{port}', '{user}', '{pass}', '{path}', '{query}', '{fragment}', '{url}'),
			array($urlParts['scheme'], $urlParts['host'], $urlParts['port'], $urlParts['user'], $urlParts['pass'], trim($urlParts['path'], '/'), $urlParts['query'], $urlParts['fragment'], $urlToPurge),
			$endpointUrl
		);

		$curlHandle = curl_init($finalEndpointUrl);
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PURGE');
		curl_setopt($curlHandle, CURLOPT_HEADER, 0);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		return $curlHandle;
	}

	/**
	 * call the execution of the HTTP requests (queue worker)
	 */
	public function __destruct() {
		$this->executeCacheFlush();
	}

}
