<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Cache;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;

class FedexResponseCacheKeyTest extends TestCase
{
    public function testGetters(): void
    {
        $request = new FedexRequest('test/uri');
        $settings = new FedexIntegrationSettings();

        $key = new FedexResponseCacheKey($request, $settings);

        self::assertSame($request, $key->getRequest());
        self::assertSame($settings, $key->getSettings());
        self::assertSame(
            (string) crc32(serialize($request->getRequestData())),
            $key->getCacheKey()
        );
    }
}
