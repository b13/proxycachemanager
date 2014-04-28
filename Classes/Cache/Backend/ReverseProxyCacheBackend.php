<?php

namespace B13\Proxycachemanager\Cache\Backend;

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
 * @package B13\Proxycachemanager\Cache\Backend
 */
class ReverseProxyCacheBackend extends \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend {

	/**
	 * @var \B13\Proxycachemanager\Provider\ProxyProviderInterface
	 */
	protected $reverseProxyProvider;

	/**
	 * set from the AbstractCacheBackend when the object is instantiated
	 */
	public function setReverseProxyProvider($className) {
		if (empty($className)) {
			throw new \InvalidArgumentException('Invalid cache proxy provider for Reverse Proxy Cache', 1231267264);
		} else {
			try {
				$className = str_replace('_', '\\', $className);
				$this->reverseProxyProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
			} catch (\Exception $e) {
				throw new \InvalidArgumentException('Invalid cache proxy provider class for Reverse Proxy Cache - Class "' . $className . '" not found.', 1231267264);
			}
		}
	}

	/**
	 * set the hostnames of the reverse proxies
	 * set from the AbstractCacheBackend when the object is instantiated
	 */
	public function setReverseProxyEndpoints($endpoints = NULL) {
		// assume it the reverse proxy is on the same host
		if (empty($endpoints)) {
			$endpoints = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
		}
		$endpoints = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $endpoints);
		$this->reverseProxyProvider->setProxyEndpoints($endpoints);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	public function remove($entryIdentifier) {

		// call the provider to forget this URL
		$url = $this->get($entryIdentifier);
		if ($url) {
			$this->reverseProxyProvider->flushCacheForUrl($url);
		}

		return parent::remove($entryIdentifier);
	}

	/**
	 * Removes all cache entries of this cache.
	 * Also let the proxy provider know to clear everything as well
	 *
	 * @return void
	 */
	public function flush() {

		$urls = $this->getAllCachedUrls();

		parent::flush();

		// make the HTTP Purge call
		$this->reverseProxyProvider->flushAllUrls($urls);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 */
	public function flushByTag($tag) {

		$identifiers = $this->findIdentifiersByTag($tag);
		foreach ($identifiers as $entryIdentifier) {
			$url = $this->get($entryIdentifier);
			if ($url) {
				$this->reverseProxyProvider->flushCacheForUrl($url);
			}
		}

		parent::flushByTag($tag);
	}
	
	/**
	 * fetch all URLs in the cache
	 */
	public function getAllCachedUrls() {
		$urls = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('content', $this->cacheTable, '', '', '', '', 'content');
		if (is_array($urls)) {
			return array_keys($urls);
		} else {
			return array();
		}
	}
}
