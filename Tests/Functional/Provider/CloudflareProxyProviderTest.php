<?php

namespace B13\Proxycachemanager\Tests\Functional\Provider;

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

use B13\Proxycachemanager\Provider\CloudflareProxyProvider;
use Psr\Log\AbstractLogger;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CloudflareProxyProviderTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function testIfFailuresAreLogged(): void
    {
        $logger = new class() extends AbstractLogger {
            /**
             * @var int
             */
            public $logsCalls = 0;
            public function log($level, $message, array $context = [])
            {
                $this->logsCalls++;
            }
        };
        putenv('CLOUDFLARE_API_TOKEN=TEST');
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['proxycachemanager']['cloudflare']['zones']['example.com'] = 'INVALID_ZONE_ID';
        $subject = new CloudflareProxyProvider();
        $subject->setLogger($logger);
        $subject->flushCacheForUrl('https://example.com/any-url');
        self::assertEquals(1, $logger->logsCalls);
    }
}
