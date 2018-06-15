TYPO3 Extension: proxycachemanager
==================================

A flexible and generic way to track the pages that are cached by a reverse proxy like nginx or varnish, or any
classic CDN.

What does it do?
----------------
By embracing TYPO3's Caching Framework this extension provides a new cache to track all pages outputted that are
"cacheable". When an editor changes content on a page, the page cache needs to be cleared - and the CDN / reverse proxy
needs to be informed that the cache is invalid. This is usually done via a HTTP PURGE request to the CDN / proxy server.

The benefits for that are that the editor does not need to worry why out-of-date information is still visible
on his/her website.

Requirements
------------
 * A TYPO3 setup (7 LTS+) with cacheable content, see the "cacheinfo" extension for what can be tracked with HTTP caches.
 * Either nginx or varnish configured to proxy the TYPO3 system. For nginx, make sure that nginx is compiled to allow to flush caches via HTTP PURGE (see #### for more information).

Setup
-----
Install the extension and make sure to enter the detail about your proxy servers, otherwise the default (IENV:TYPO3_REV_PROXY) is used.

Don't forget to set the according TYPO3 settings for using proxies, see $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] and $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'].

Whenever a cacheable frontend page is now called, the full URL is stored in a cache called "tx_proxy" automatically (a derivative of the Typo3DatabaseBackend cache), tagged with the pageID. Whenever the cache is flushed, the database table is emptied completely, additionally, a HTTP PURGE call to the reverse proxy is made to empty all caches. If only a certain tag is removed, the PURGE call is made only to the relevant URLs that are stored in the cache.

Credits
-------
The extension was created taken all the great from Andreas Gellhaus, Tom Rüther into account, as well as moc_varnish and cacheinfo as great work before.

