<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;

use OroB2B\Bundle\WarehouseBundle\Form\Type\InventoryLevelExportTemplateTypeExtension;

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

    public function testBuildFormShouldaddEventListener()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('addEventListener');

        $this->inventoryLevelExportTemplateTypeExtension->buildForm($builder, []);
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('remove')
            ->with(ExportTemplateType::CHILD_PROCESSOR_ALIAS);

        $this->inventoryLevelExportTemplateTypeExtension->buildForm($builder, []);
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $builder = $this->getBuilderMock();
        $phpunitTestCase = $this;

        $builder->expects($this->once())
            ->method('add')
            ->will($this->returnCallback(function ($name, $type, $options) use ($phpunitTestCase) {
                $choices = $options['choices'][0];
                $phpunitTestCase->assertArrayHasKey(
                    InventoryLevelExportTemplateTypeExtension::$processorAliases[0],
                    $choices
                );
                $phpunitTestCase->assertArrayHasKey(
                    InventoryLevelExportTemplateTypeExtension::$processorAliases[1],
                    $choices
                );
            }));

        $this->inventoryLevelExportTemplateTypeExtension->buildForm($builder, []);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock()
    {
        return $this->getMock(FormBuilderInterface::class);
    }
}
