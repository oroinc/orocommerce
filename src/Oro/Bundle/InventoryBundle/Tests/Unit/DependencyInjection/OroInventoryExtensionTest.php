<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;

class OroInventoryExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroInventoryExtension());

        $expectedDefinitions = [
            'oro_inventory.inventory_level.manager.api',
            'oro_inventory.api.processor.product_id.normalize_input',
            'oro_inventory.api.processor.entity_id.load_data',
            'oro_inventory.api.processor.update_inventory_level.build_query',
            'oro_inventory.api.processor.create_inventory_level.normalize_input',

        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = ['oro_inventory'];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    public function testGetAlias()
    {
        $extension = new OroInventoryExtension();
        $this->assertEquals('oro_inventory', $extension->getAlias());
    }

    /**
     * @param Extension $extension
     * @param array $config
     * @return $this
     */
    protected function loadExtension(Extension $extension, $config = [])
    {
        $container = $this->getContainerMock();

        $extension->load($config, $container);

        return $this;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected function buildContainerMock()
    {
        return $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig', 'getParameter'])
            ->getMock();
    }
}
