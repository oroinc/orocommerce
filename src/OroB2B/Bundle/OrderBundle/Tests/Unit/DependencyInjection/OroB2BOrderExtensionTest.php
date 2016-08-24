<?php
namespace OroB2B\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\OrderBundle\DependencyInjection\OroB2BOrderExtension;

class OroB2BOrderExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroB2BOrderExtension());

        $expectedParameters = [
            'orob2b_order.entity.order.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'orob2b_order.form.type.order',
            'orob2b_order.order.manager.api',
            'orob2b_order.layout.provider.order_shipping_method',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroB2BOrderExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroB2BOrderExtension();
        $this->assertEquals(OroB2BOrderExtension::ALIAS, $extension->getAlias());
    }
}
