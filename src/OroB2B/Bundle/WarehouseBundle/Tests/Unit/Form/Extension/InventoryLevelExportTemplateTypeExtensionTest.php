<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\WarehouseBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelExportTemplateTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryLevelExportTemplateTypeExtension
     */
    protected $inventoryLevelExportTemplateTypeExtension;

    protected function setUp()
    {
        $this->inventoryLevelExportTemplateTypeExtension = new InventoryLevelExportTemplateTypeExtension();
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('remove')
            ->with('processorAlias');

        $this->inventoryLevelExportTemplateTypeExtension->buildForm(
            $builder,
            ['entityName' => WarehouseInventoryLevel::class]
        );
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $processorAliases = [
            'orob2b_product.inventory_status_only_export_template',
            'orob2b_warehouse.inventory_level_export_template'
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

        $this->inventoryLevelExportTemplateTypeExtension->buildForm(
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
