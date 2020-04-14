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

    protected function setUp(): void
    {
        $this->extension = new OroFedexShippingExtension();
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_fedex_shipping.integration.channel',
            'oro_fedex_shipping.integration.identifier_generator',
            'oro_fedex_shipping.integration.transport',
            'oro_fedex_shipping.client.rate_service.response.factory',
            'oro_fedex_shipping.client.rate_service',
            'oro_fedex_shipping.client.rate_service.request.factory',
            'oro_fedex_shipping.transfomer.shipping_dimensions_unit',
            'oro_fedex_shipping.transfomer.shipping_weight_unit',
            'oro_fedex_shipping.modifier.convert_to_fedex_units_shipping_line_item_collection',
            'oro_fedex_shipping.factory.fedex_package_settings_by_integration_settings_and_rule',
            'oro_fedex_shipping.factory.fedex_package_by_shipping_package_options',
            'oro_fedex_shipping.factory.fedex_packages_by_line_items_and_package_settings',
            'oro_fedex_shipping.builder.shipping_packages_by_line_item',
            'oro_fedex_shipping.client.rate_service.soap_settings',
            'oro_fedex_shipping.client.rate_service.connection_validate_request.factory',
            'oro_fedex_shipping.form.type.shipping_method_options',
            'oro_fedex_shipping.shipping_method.method_type_identifier_generator',
            'oro_fedex_shipping.shipping_method.factory.method_type',
            'oro_fedex_shipping.shipping_method.factory.method',
            'oro_fedex_shipping.shipping_method.provider',
            'oro_fedex_shipping.event_listener.remove_integration',
            'oro_fedex_shipping.event_listener.shipping_method_config_data',
            'oro_fedex_shipping.event_listener.disable_integration',
            'oro_fedex_shipping.entity_listener.delete_integration_settings_services',
            'oro_fedex_shipping.cache.cache_key_factory',
            'oro_fedex_shipping.cache.response',
            'oro_fedex_shipping.client.rate_service_cached',
            'oro_fedex_shipping.cache',
            'oro_fedex_shipping.validator.remove_used_shipping_service',
            'oro_fedex_shipping.client.rate_service.soap_test_settings',
            'oro_fedex_shipping.client.rate_service.request.settings.factory',
            'oro_fedex_shipping.expression_language',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedParameters = [
            'oro_fedex_shipping.integration.channel.type',
            'oro_fedex_shipping.integration.transport.type',
            'oro_fedex_shipping.shipping_rule.method_template',
        ];

        $this->assertParametersLoaded($expectedParameters);
    }

    public function testGetAlias()
    {
        static::assertSame('oro_fedex_shipping', $this->extension->getAlias());
    }
}
