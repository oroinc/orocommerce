<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\InventoryBundle\DependencyInjection\OroWarehouseExtension;

class OroWarehouseExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroWarehouseExtension());

        $expectedParameters = [
            'oro_warehouse.entity.warehouse.class',
            'oro_warehouse.entity.warehouse_inventory_level.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_warehouse.warehouse_inventory_level.manager.api',
            'oro_warehouse.entity.helper.warehouse_counter',
            'oro_warehouse.api.processor.product_id.normalize_input',
            'oro_warehouse.api.processor.entity_id.load_data',
            'oro_warehouse.api.processor.update_warehouse_inventory_level.build_query',
            'oro_warehouse.api.processor.create_warehouse_inventory_level.normalize_input',
            'oro_warehouse.form.autocomplete.warehouse.search_handler',
            'oro_warehouse.validator.unique_warehouse',
            'oro_warehouse.event_listener.system_config',
            'oro_warehouse.system_config_converter',
            'oro_warehouse.form.type.warehouse',
            'oro_warehouse.form.type.warehouse_inventoty_level_grid',
            'oro_warehouse.form.type.extension.warehouse_inventory_status_export',
            'oro_warehouse.form.type.extension.warehouse_inventory_level_export_template',
            'oro_warehouse.form.type.warehouse_select',
            'oro_warehouse.form.type.warehouse_select_with_priority',
            'oro_warehouse.form.type.warehouse_system_config',
            'oro_warehouse.form.warehouse_collection'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = ['oro_warehouse'];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }

    public function testGetAlias()
    {
        $extension = new OroWarehouseExtension();
        $this->assertEquals('oro_warehouse', $extension->getAlias());
    }

    /**
     * @param Extension $extension
     * @param array $config
     * @return $this
     */
    protected function loadExtension(Extension $extension, $config = [])
    {
        $container = $this->getContainerMock();
        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn([]);

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
