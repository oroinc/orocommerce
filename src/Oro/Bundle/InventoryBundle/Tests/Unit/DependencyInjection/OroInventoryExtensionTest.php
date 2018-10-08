<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroInventoryExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroInventoryExtension());

        $expectedExtensionConfigs = ['oro_inventory'];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);

        $expectedDefinitions = [
            'oro_inventory.importexport.configuration_provider.inventory_level',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected function buildContainerMock()
    {
        return $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig', 'getParameter'])
            ->getMock();
    }
}
