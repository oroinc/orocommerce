<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Settings\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use PHPUnit\Framework\TestCase;

class FedexRateServiceRequestSettingsFactoryTest extends TestCase
{
    public function testCreate()
    {
        $integrationSettings = new FedexIntegrationSettings();
        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $rule = new ShippingServiceRule();

        static::assertEquals(
            new FedexRateServiceRequestSettings($integrationSettings, $shippingContext, $rule),
            (new FedexRateServiceRequestSettingsFactory())->create($integrationSettings, $shippingContext, $rule)
        );
    }
}
