<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Cache;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;

class FedexResponseCacheKeyTest extends TestCase
{
    public function testGetters()
    {
        $request = new FedexRequest();
        $settings = new FedexIntegrationSettings();

        $key = new FedexResponseCacheKey($request, $settings);

        static::assertSame($request, $key->getRequest());
        static::assertSame($settings, $key->getSettings());
        static::assertSame(
            (string) crc32(serialize($request->getRequestData())),
            $key->getCacheKey()
        );
    }
}
