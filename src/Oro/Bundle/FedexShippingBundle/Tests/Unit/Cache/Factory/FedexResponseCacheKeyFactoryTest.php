<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Cache\Factory;

use Oro\Bundle\FedexShippingBundle\Cache\Factory\FedexResponseCacheKeyFactory;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;

class FedexResponseCacheKeyFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $request = new FedexRequest('test/uri');
        $settings = new FedexIntegrationSettings();

        self::assertEquals(
            new FedexResponseCacheKey($request, $settings),
            (new FedexResponseCacheKeyFactory())->create($request, $settings)
        );
    }
}
