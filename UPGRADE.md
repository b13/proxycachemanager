# Upgrade Instructions

v3 => v4

Proxycachemanager v3 served for many years as a well thought out basis
for extending the interface for any kind of proxy and CDN adapter.

However, some functionality has been removed and adapted,
here is a list of features that have been removed or changed:

## Removed Functionality

* Cloudflare CDN Cache Provider
* Curl HTTP Cache Provider
* Fastly CDN Cache Provider
* Varnish HTTP Cache Provider

These features can be added in separate extensions requiring
proxycachemanager, and utilizing even more functionality.

In case you need them, we suggest moving the code from "proxycachemanager"
v3 into your extension code, and then adapt to the updated
interface.

## Adapted "ProxyProviderInterface"

The main interface "ProxyProviderInterface" has the following methods removed:

* "setProxyEndpoints" - Not every Cache Provider ships an endpoint, so this can be handled individually
* "flushCacheForUrl($url)" - Use "flushCacheForUrls(array $urls): void" instead

New methods have been introduced:
* "isActive(): bool" to define if a Proxy Cache Provider has been set up properly
* "shouldRequestBeMarkedAsCached(RequestInterface $request): bool" allows to define whether a frontend page is behind a reverse proxy and should be added to the cache

In addition, the interface is now fully typed.

The TYPO3 Extension now required PHP 8.1 and TYPO3 v11 LTS.