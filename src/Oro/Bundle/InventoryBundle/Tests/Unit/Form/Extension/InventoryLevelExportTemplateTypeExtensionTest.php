<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

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
            ['entityName' => InventoryLevel::class]
        );
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $processorAliases = [
            'oro_product.inventory_status_only_template',
            'oro_warehouse.detailed_inventory_levels_template'
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
            ['entityName' => InventoryLevel::class]
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
