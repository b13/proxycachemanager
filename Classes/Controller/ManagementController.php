<?php

declare(strict_types=1);

namespace B13\Proxycachemanager\Controller;

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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ManagementController extends ActionController
{
    protected ProxyProviderInterface $proxyProvider;

    public function __construct(protected ModuleTemplateFactory $moduleTemplateFactory, ProxyConfiguration $configuration)
    {
        $this->proxyProvider = $configuration->getProxyProvider();
    }

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() === 11) {
            $this->view->setTemplateRootPaths(['EXT:proxycachemanager/Resources/Private/TemplatesV11/']);
            $moduleTemplate->setContent($this->view->render());
            $response = $this->htmlResponse($moduleTemplate->renderContent());
        } else {
            $response = $moduleTemplate->renderResponse('Management/Index');
        }
        return $response;
    }

    /**
     * @param string $tag
     */
    public function clearTagAction(string $tag): ResponseInterface
    {
        GeneralUtility::makeInstance(CacheManager::class)->flushCachesByTags([htmlspecialchars($tag)]);
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() === 11) {
            $severity = AbstractMessage::OK;
        } else {
            $severity = ContextualFeedbackSeverity::OK;
        }
        $this->addFlashMessage(
            'Successfully purged cache tag "' . htmlspecialchars($tag) . '".',
            'Cache flushed',
            $severity
        );
        return new RedirectResponse($this->uriBuilder->reset()->uriFor('index'));
    }

    /**
     * @param string $url
     */
    public function purgeUrlAction(string $url): ResponseInterface
    {
        if (empty($url)) {
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() === 11) {
                $severity = AbstractMessage::WARNING;
            } else {
                $severity = ContextualFeedbackSeverity::WARNING;
            }
            $this->addFlashMessage(
                'Please specify url',
                'Cache not flushed',
                AbstractMessage::WARNING
            );
            return new RedirectResponse($this->uriBuilder->reset()->uriFor('index'));
        }
        $url = htmlspecialchars($url);
        if (!$this->proxyProvider->isActive()) {
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() === 11) {
                $severity = AbstractMessage::ERROR;
            } else {
                $severity = ContextualFeedbackSeverity::ERROR;
            }
            $this->addFlashMessage(
                'Attempting to purge URL "' . $url . '". No active provider configured.',
                'Cache not flushed',
                $severity
            );
            return new RedirectResponse($this->uriBuilder->reset()->uriFor('index'));
        }

        $this->proxyProvider->flushCacheForUrls([$url]);
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() === 11) {
            $severity = AbstractMessage::OK;
        } else {
            $severity = ContextualFeedbackSeverity::OK;
        }
        $this->addFlashMessage(
            'Successfully purged URL "' . $url . '".',
            'Cache flushed',
            $severity
        );
        return new RedirectResponse($this->uriBuilder->reset()->uriFor('index'));
    }
}
