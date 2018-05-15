<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Settings;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use PHPUnit\Framework\TestCase;

class FedexRateServiceRequestSettingsTest extends TestCase
{
    public function testGetters()
    {
        $integrationSettings = new FedexIntegrationSettings();
        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $rule = new ShippingServiceRule();

        $settings = new FedexRateServiceRequestSettings($integrationSettings, $shippingContext, $rule);

        static::assertSame($integrationSettings, $settings->getIntegrationSettings());
        static::assertSame($shippingContext, $settings->getShippingContext());
        static::assertSame($rule, $settings->getShippingServiceRule());
    }
}
