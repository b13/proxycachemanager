# Upgrade Instructions

v4 => v5

Proxycachemanager v5 drops support for TYPO3 v11 and v12. The minimum
supported version is now TYPO3 v13 LTS; v14 is fully supported.

## Removed

* `Classes/Hook/FrontendHook.php` — the v11-only `tslib/class.tslib_fe.php`
  `insertPageIncache` hook. Cache writes are handled exclusively via the
  `AfterCachedPageIsPersistedEvent` listener now.
* `Resources/Private/TemplatesV11/` — fallback Fluid template for the v11
  backend module.
* `ext_tables.php` — v11-only `ExtensionUtility::registerModule()` call.
  The module is registered through `Configuration/Backend/Modules.php` on
  v12 and up.

## Changed

* `Classes/Listener/AfterCacheIsPersisted` no longer calls
  `$event->getController()->getPageCacheTags()` — TYPO3 v14 dropped the
  `controller` argument from `AfterCachedPageIsPersistedEvent`. Cache tags
  are now read from `$event->getCacheData()['cacheTags']`, which TYPO3
  populates in both v13 and v14.
* `Classes/Controller/ManagementController` no longer branches on the
  TYPO3 major version. `\TYPO3\CMS\Core\Messaging\AbstractMessage::OK`,
  `::WARNING`, `::ERROR` were removed in TYPO3 v14; the controller now
  always uses `ContextualFeedbackSeverity::*` enum cases.

## Required platform

* PHP 8.2+
* TYPO3 13.4+ or 14.x

---

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