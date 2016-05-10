<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\OroB2BShippingExtension;

class OroB2BShippingExtensionTest extends ExtensionTestCase
{
    /** @var OroB2BShippingExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroB2BShippingExtension();
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
            'orob2b_shipping.factory.shipping_origin_model_factory',
            'orob2b_shipping.event_listener.config.shipping_origin'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroB2BShippingExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroB2BShippingExtension::ALIAS, $this->extension->getAlias());
    }
}
