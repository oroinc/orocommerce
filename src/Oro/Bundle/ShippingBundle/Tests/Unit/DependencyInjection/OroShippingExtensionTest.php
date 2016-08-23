<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;

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
            'orob2b_shipping.form.type.shipping_origin_config',
            'orob2b_shipping.form.type.shipping_origin_warehouse',
            'orob2b_shipping.form.extension.warehouse_shipping_origin',
            'orob2b_shipping.form_event_subscriber.rule_configuration_subscriber',
            'orob2b_shipping.factory.shipping_origin_model_factory',
            'orob2b_shipping.event_listener.config.shipping_origin',
            'orob2b_shipping.shipping_method.registry',
            'orob2b_shipping.shipping_method.flat_rate',
            'orob2b_shipping.provider.shipping_rules',
            'orob2b_shipping.formatter.shipping_method_label',
            'orob2b_shipping.twig.shipping_method_extension',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroShippingExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroShippingExtension::ALIAS, $this->extension->getAlias());
    }
}
