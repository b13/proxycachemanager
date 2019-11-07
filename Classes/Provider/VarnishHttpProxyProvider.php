<?php
declare(strict_types = 1);
namespace B13\Proxycachemanager\Provider;

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
 * can use some varnish specific calls
 * todo: add what is needed for varnish.
 */
class VarnishHttpProxyProvider extends CurlHttpProxyProvider
{
    /**
     * flushes the whole proxy cache (directly).
     *
     * @param array $urls
     */
    public function flushAllUrls($urls = [])
    {
        $this->queue = ['.*'];
        $this->executeCacheFlush();
    }
}
