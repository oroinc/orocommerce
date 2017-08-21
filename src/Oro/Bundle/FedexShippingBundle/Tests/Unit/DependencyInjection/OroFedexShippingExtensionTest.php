<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FedexShippingBundle\DependencyInjection\OroFedexShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFedexShippingExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroFedexShippingExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->extension = new OroFedexShippingExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_fedex_shipping.integration.channel',
            'oro_fedex_shipping.client.rate_service.response.factory',
            'oro_fedex_shipping.client.rate_service',
            'oro_fedex_shipping.client.rate_service.request.factory',
            'oro_fedex_shipping.transfomer.shipping_dimensions_unit',
            'oro_fedex_shipping.transfomer.shipping_weight_unit',
            'oro_fedex_shipping.modifier.convert_to_fedex_units_shipping_line_item_collection',
            'oro_fedex_shipping.factory.fedex_package_settings_by_integration_settings',
            'oro_fedex_shipping.factory.fedex_package_by_shipping_package_options',
            'oro_fedex_shipping.factory.fedex_packages_by_line_items_and_package_settings',
            'oro_fedex_shipping.builder.shipping_packages_by_line_item',
            'oro_fedex_shipping.client.rate_service.soap_settings',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [
            'oro_fedex_shipping.integration.channel.type',
        ];

        $this->assertParametersLoaded($expectedParameters);
    }

    public function testGetAlias()
    {
        static::assertSame('oro_fedex_shipping', $this->extension->getAlias());
    }
}
