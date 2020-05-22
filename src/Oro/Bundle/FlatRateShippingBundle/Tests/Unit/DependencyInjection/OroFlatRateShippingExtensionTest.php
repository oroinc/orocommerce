<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FlatRateShippingBundle\DependencyInjection\OroFlatRateShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFlatRateShippingExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroFlatRateShippingExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroFlatRateShippingExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_flat_rate_shipping.integration.channel',
            'oro_flat_rate_shipping.integration.transport',
            'oro_flat_rate_shipping.event_listener.shipping_method_config_data',
            'oro_flat_rate_shipping.form.type.flat_rate_options',
            'oro_flat_rate_shipping.method.provider',
            'oro_flat_rate_shipping.disable_integration_listener',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAliasReturnsString()
    {
        $this->assertTrue(is_string($this->extension->getAlias()));
    }
}
