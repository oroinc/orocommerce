<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;

class OroCheckoutExtensionTest extends ExtensionTestCase
{
    /** @var OroCheckoutExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroCheckoutExtension();
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
            'orob2b_checkout.condition.has_applicable_shipping_methods',
            'orob2b_checkout.condition.shipping_method_supports'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroCheckoutExtension::ALIAS]);
    }

    public function testGetAlias()
    {
        $this->assertEquals(OroCheckoutExtension::ALIAS, $this->extension->getAlias());
    }
}
