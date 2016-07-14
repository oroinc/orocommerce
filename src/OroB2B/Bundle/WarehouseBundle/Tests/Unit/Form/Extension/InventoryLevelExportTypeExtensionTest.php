<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;

use OroB2B\Bundle\WarehouseBundle\Form\Extension\InventoryLevelExportTypeExtension;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

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

    public function testBuildFormShouldaddEventListener()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('addEventListener');

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => WarehouseInventoryLevel::class]
        );
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('remove')
            ->with(ExportType::CHILD_PROCESSOR_ALIAS);

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => WarehouseInventoryLevel::class]
        );
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $processorAliases = [
            'orob2b_product.export_inventory_status_only',
            'orob2b_warehouse.detailed_inventory_levels'
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
