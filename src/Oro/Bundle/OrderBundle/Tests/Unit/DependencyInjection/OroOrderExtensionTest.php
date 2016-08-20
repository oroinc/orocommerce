<?php
namespace Oro\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;

class OroOrderExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroOrderExtension());

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

        $this->assertExtensionConfigsLoaded([OroOrderExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroOrderExtension();
        $this->assertEquals(OroOrderExtension::ALIAS, $extension->getAlias());
    }
}
