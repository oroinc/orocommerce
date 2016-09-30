<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\WarehouseBundle\Form\Extension\InventoryLevelExportTypeExtension;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelExportTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryLevelExportTypeExtension
     */
    protected $inventoryLevelExportTypeExtension;

    protected function setUp()
    {
        $this->inventoryLevelExportTypeExtension = new InventoryLevelExportTypeExtension();
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('remove')
            ->with('processorAlias');

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => WarehouseInventoryLevel::class]
        );
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $processorAliases = [
            'oro_product.inventory_status_only',
            'oro_warehouse.detailed_inventory_levels'
        ];

        $builder = $this->getBuilderMock();
        $phpunitTestCase = $this;

        $builder->expects($this->once())
            ->method('add')
            ->will($this->returnCallback(function ($name, $type, $options) use ($phpunitTestCase, $processorAliases) {
                $choices = $options['choices'];
                $phpunitTestCase->assertArrayHasKey(
                    $processorAliases[0],
                    $choices
                );
                $phpunitTestCase->assertArrayHasKey(
                    $processorAliases[1],
                    $choices
                );
            }));

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => WarehouseInventoryLevel::class]
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder(FormBuilderInterface::class)->getMock();
    }
}
