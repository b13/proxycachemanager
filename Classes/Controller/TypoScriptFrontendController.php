<?php
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

/**
 * XCLASS of TypoScriptFrontendController
 *
 * This XCLASS only adds a getter for TSFE->pageCacheTags
 */
class TypoScriptFrontendController extends \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
{
    /**
     * @return array
     */
    public function getPageCacheTags(): array
    {
        return $this->pageCacheTags ?? [];
    }
}
