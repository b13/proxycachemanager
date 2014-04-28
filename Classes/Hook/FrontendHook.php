<?php

namespace B13\Proxycachemanager\Hook;

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
 * class containing frontend-related hooks
 *
 * @package B13\Proxycachemanager\Hook
 */
class FrontendHook {

	/**
	 * hook that is called when a cacheable page is ready for output
	 * calls the proxy cache and stores the pageId, the URL
	 * this call costs a little bit of performance but is only called
	 * once (!) as the URL is cacheable, next time it is fetched
	 * from the reverse proxy directly.
	 * @param array $parameters
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $parentObject
	 */
	public function addCacheableUrlToProxyCache($parameters, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $parentObject) {

		$cache = $GLOBALS['typo3CacheManager']->getCache('tx_proxy');
		$pageUid = $parentObject->id;

		// cache the page URL that was called
		$url = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		$cache->set(md5($url), $url, array('pageId_' . $pageUid));
		$this->getLogObject()->info(
			'Marking page "%s" (uid %s) as cached.',
			array($url, $pageUid)
		);


		foreach ($parentObject->imagesOnPage as $imageUrl) {
			$url = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $imageUrl;
			$cache->set(md5($url), $url, array('pageId_' . $pageUid));
			$this->getLogObject()->info(
				'Marking image "%s" (on page %s) as cached.',
				array($url, $pageUid)
			);

		}

	}

	/**
	 * return \TYPO3\CMS\Core\Log\Logger
	 */
	protected function getLogObject() {
		static $logObject;
		if (!$logObject) {
			$logObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
		}
		return $logObject;
	}
}
