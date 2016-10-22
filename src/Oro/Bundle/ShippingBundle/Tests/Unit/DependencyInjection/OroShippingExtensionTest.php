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
            'oro_shipping.form.type.shipping_origin_warehouse',
            'oro_shipping.form.extension.warehouse_shipping_origin',
            'oro_shipping.form_event_subscriber.rule_method_type_config_collection_subscriber',
            'oro_shipping.form_event_subscriber.rule_method_config_subscriber',
            'oro_shipping.factory.shipping_origin_model_factory',
            'oro_shipping.event_listener.config.shipping_origin',
            'oro_shipping.shipping_method.registry',
            'oro_shipping.shipping_method_provider.flat_rate',
            'oro_shipping.formatter.shipping_method_label',
            'oro_shipping.twig.shipping_method_extension',
            'oro_shipping.shipping_price.provider',
            'oro_shipping.provider.measure_units.conversion',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShippingExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroShippingExtension::ALIAS, $this->extension->getAlias());
    }
}
