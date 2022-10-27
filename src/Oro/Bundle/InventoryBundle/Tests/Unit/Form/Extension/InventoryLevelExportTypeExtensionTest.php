<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class InventoryLevelExportTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var InventoryLevelExportTypeExtension */
    private $inventoryLevelExportTypeExtension;

    protected function setUp(): void
    {
        $this->inventoryLevelExportTypeExtension = new InventoryLevelExportTypeExtension();
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('remove')
            ->with('processorAlias');

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => InventoryLevel::class]
        );
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) {
                $choices = $options['choices'];
                $this->assertContains('oro_product.inventory_status_only', $choices);
                $this->assertContains('oro_inventory.detailed_inventory_levels', $choices);
            });

        $this->inventoryLevelExportTypeExtension->buildForm(
            $builder,
            ['entityName' => InventoryLevel::class]
        );
    }
}
