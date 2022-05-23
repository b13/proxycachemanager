<?php

namespace B13\Proxycachemanager\Tests\Unit\Provider;

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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class CloudflareProxyProviderTest
 */
class CloudflareProxyProviderTest extends UnitTestCase
{

    /**
     * @test
     */
    public function groupUrlsByAllowedZonesReturnsTheOriginalUrlsIfHostMatch()
    {
        $zoneId = 'baz';
        $mock = $this->getAccessibleMock(CloudflareProxyProvider::class, ['getZones']);
        $mock->expects(self::once())->method('getZones')->willReturn(['foo.bar' => $zoneId]);
        $cachedUrls = ['https://foo.bar/bazz'];
        $urls = $mock->_call('groupUrlsByAllowedZones', $cachedUrls);
        self::assertSame([$zoneId => $cachedUrls], $urls);
    }

    /**
     * @test
     */
    public function groupUrlsByAllowedZonesReturnsEmptyArrayIfHostNotMatch()
    {
        $zoneId = 'baz';
        $mock = $this->getAccessibleMock(CloudflareProxyProvider::class, ['getZones']);
        $mock->expects(self::once())->method('getZones')->willReturn(['foo.bar' => $zoneId]);
        $cachedUrls = ['https://bar/bazz'];
        $urls = $mock->_call('groupUrlsByAllowedZones', $cachedUrls);
        self::assertSame([$zoneId => []], $urls);
    }

    /**
     * @test
     */
    public function groupUrlsByAllowedZonesRemovesNonMatchingHostUrls()
    {
        $zoneId = 'baz';
        $mock = $this->getAccessibleMock(CloudflareProxyProvider::class, ['getZones']);
        $mock->expects(self::once())->method('getZones')->willReturn(['foo.bar' => $zoneId]);
        $cachedUrls = [
            'https://foo.bar/bazz',
            'https://bar/bazz',
        ];
        $urls = $mock->_call('groupUrlsByAllowedZones', $cachedUrls);
        self::assertSame([$zoneId => ['https://foo.bar/bazz']], $urls);
    }
}
