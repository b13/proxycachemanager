<?php

declare(strict_types=1);
namespace B13\Proxycachemanager\ResourceStorageOperations\Listener;

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

use TYPO3\CMS\Core\Resource\Event\BeforeFolderDeletedEvent;

class BeforeFolderDeleted extends AbstractListener
{
    public function __invoke(BeforeFolderDeletedEvent $e): void
    {
        $this->cacheService->flushCachesForFolder($e->getFolder());
    }
}
