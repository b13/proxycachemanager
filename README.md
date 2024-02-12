Proxy Cache Manager
===================

This TYPO3 extension is a flexible and generic way to track the pages that are cached by a reverse proxy like
nginx or varnish, or any useful CDN.

Currently supported backends / providers:
 * Cloudflare CDN (https://packagist.org/packages/b13/cloudflare-cdn)
 * Akamai CDN (https://packagist.org/packages/b13/akamai)
 * Azure CDN (https://packagist.org/packages/b13/azure-purge)

What does it do?
----------------
By embracing TYPO3's Caching Framework this extension provides a new cache to track all pages outputted that are
"cacheable". When an editor changes content on a page, the page cache needs to be cleared - and the CDN / reverse proxy
needs to be informed that the cache is invalid. This is usually done via a HTTP PURGE request to the CDN / proxy server
or a custom API.

The benefits for that are that the editor does not need to worry why out-of-date information is still visible
on his/her website.

Requirements
------------
 * A TYPO3 setup (v11 LTS+) with cacheable content, see the "cacheinfo" extension for what can be tracked with HTTP caches.
 * A CDN or Reverse Proxy (such as nginx or apache2)

Setup
-----
Install the extension and make sure to enter the details about your proxy servers, otherwise the default (`IENV:TYPO3_REV_PROXY`) is used.

Don't forget to set the according TYPO3 settings for using proxies, see `$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']` and `$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue']`.

Whenever a cacheable frontend page is now called, the full URL is stored in a cache called "tx_proxy" automatically
(a derivative of the Typo3DatabaseBackend cache), tagged with the pageID. Whenever the cache is flushed, the database
table is emptied completely, additionally, a HTTP PURGE call to the reverse proxy is made to empty all caches.
If only a certain tag is removed, the PURGE call is made only to the relevant URLs that are stored in the cache.

### Configuration

By default all administrators can flush the CDN caches via the toolbar
on the top right corner of TYPO3's Backend.

To enable the button for non-admin editors, use this UserTsConfig option:

`options.clearCache.proxy = 1`

To explicitly disable the button for specific administrators, use this
UserTsConfig option:

`options.clearCache.proxy = 0`

Credits
-------

The extension was created taken all the great from Andreas Gellhaus, Tom Rüther into account, as well
as `moc_varnish` and `cacheinfo` as great work before.

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
