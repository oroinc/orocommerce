<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroShippingExtensionTest extends ExtensionTestCase
{
    /** @var OroShippingExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroShippingExtension();
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'oro_shipping.form.type.shipping_origin_config',
            'oro_shipping.form_event_subscriber.method_type_config_collection_subscriber',
            'oro_shipping.form_event_subscriber.method_config_subscriber',
            'oro_shipping.factory.shipping_origin_model_factory',
            'oro_shipping.event_listener.config.shipping_origin',
            'oro_shipping.shipping_method.registry',
            'oro_shipping.formatter.shipping_method_label',
            'oro_shipping.twig.shipping_method_extension',
            'oro_shipping.shipping_price.provider',
            'oro_shipping.shipping_price.provider_enabled_methods_decorator',
            'oro_shipping.provider.measure_units.conversion',
            'oro_shipping.condition.has_applicable_shipping_methods',
            'oro_shipping.method.view_factory',
            'oro_shipping.datagrid.shipping_rule_actions_visibility_provider',
            'oro_shipping.converter.shipping_context_to_rule_values',
            'oro_shipping.listener.shipping_rule',
            'oro_shipping.helper.filtered_datagrid_route',
            'oro_shipping.validator.shipping_rule_enabled',
            'oro_shipping.checker.shipping_method_enabled',
            'oro_shipping.checker.shipping_rule_enabled',
            'oro_shipping.provider.shipping_method_choices'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShippingExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroShippingExtension::ALIAS, $this->extension->getAlias());
    }
}
