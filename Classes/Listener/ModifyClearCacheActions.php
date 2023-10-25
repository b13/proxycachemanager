<?php

declare(strict_types=1);

namespace B13\Proxycachemanager\Listener;

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

use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use B13\Proxycachemanager\ProxyConfiguration;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModifyClearCacheActions
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected ProxyConfiguration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        if ($this->proxyProvider->isActive() && $this->configuration->backendFlushEnabled()) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $event->addCacheActionIdentifier('clearProxyCache');
            $item = [
                'id' => 'clearProxyCache',
                'title' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:menuitem.title',
                'description' => 'LLL:EXT:proxycachemanager/Resources/Private/Language/locallang.xlf:menuitem.description',
                'href' => $uriBuilder->buildUriFromRoute('ajax_proxy_flushcaches'),
                'iconIdentifier' => 'actions-system-cache-clear-impact-medium',
            ];
            $event->addCacheAction($item);
        }
    }
}
