<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\CheckoutBundle\DependencyInjection\OroB2BCheckoutExtension;

class OroB2BCheckoutExtensionTest extends ExtensionTestCase
{
    /** @var OroB2BCheckoutExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroB2BCheckoutExtension();
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            'orob2b_checkout.layout.data_provider.shipping_methods',
            'orob2b_checkout.shipping_cost.calculator',
            'orob2b_checkout.condition.has_applicable_shipping_methods',
            'orob2b_checkout.condition.shipping_method_supports'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroB2BCheckoutExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroB2BCheckoutExtension::ALIAS, $this->extension->getAlias());
    }
}
