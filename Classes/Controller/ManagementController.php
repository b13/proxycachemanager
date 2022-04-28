<?php
declare(strict_types = 1);
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
use GuzzleHttp\Exception\TransferException;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend
 */
class ManagementController extends ActionController
{
     protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    public function indexAction()
    {
    }

    /**
     * @param string $tag
     * @return void
     */
    public function clearTagAction(string $tag)
    {
        GeneralUtility::makeInstance(CacheManager::class)->flushCachesByTags([htmlspecialchars($tag)]);
        $this->addFlashMessage(
            'Successfully purged cache tag "' . htmlspecialchars($tag) . '".',
            'Cache flushed',
            FlashMessage::OK
        );
        $this->redirect('index');
    }

    /**
     * @param string $url
     * @return void
     */
    public function purgeUrlAction(string $url)
    {
        if (empty($url)) {
            $this->redirect('index');
        }
        $url = htmlspecialchars($url);

        $proxyProvider = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['proxycachemanager']['reverseProxyProvider']);
        if (!$proxyProvider instanceof ProxyProviderInterface) {
            $this->addFlashMessage(
                'Attempting to purge URL "' . $url . '". No matching provider configured.',
                'Cache not flushed',
                FlashMessage::ERROR
            );
            $this->redirect('index');
        }

        if (Environment::getContext()->isProduction()) {
            try {
                $proxyProvider->flushCacheForUrl($url);
                $this->addFlashMessage(
                    'Successfully purged URL "' . $url . '".',
                    'Cache flushed',
                    FlashMessage::OK
                );
            } catch (TransferException $e) {
                $this->addFlashMessage(
                    sprintf('URL "%s" could not be purged. The technical reason was "%s".', $url, htmlspecialchars($e->getMessage())),
                    'Cache not flushed',
                    FlashMessage::ERROR
                );
            }
        }

        if (Environment::getContext()->isDevelopment()) {
            $this->addFlashMessage(
                'Attempting to purge URL "' . $url . '". Feature is disabled in Development.',
                'Cache not flushed',
                FlashMessage::WARNING
            );
        }
        $this->redirect('index');
    }
}
