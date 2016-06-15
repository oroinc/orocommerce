<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;

use OroB2B\Bundle\WarehouseBundle\Form\Type\InventoryLevelExportTypeExtension;

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

        $this->inventoryLevelExportTypeExtension->buildForm($builder, []);
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('remove')
            ->with(ExportType::CHILD_PROCESSOR_ALIAS);

        $this->inventoryLevelExportTypeExtension->buildForm($builder, []);
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
                    InventoryLevelExportTypeExtension::$processorAliases[0],
                    $choices
                );
                $phpunitTestCase->assertArrayHasKey(
                    InventoryLevelExportTypeExtension::$processorAliases[1],
                    $choices
                );
            }));

        $this->inventoryLevelExportTypeExtension->buildForm($builder, []);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock()
    {
        return $this->getMock(FormBuilderInterface::class);
    }
}
