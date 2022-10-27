<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class InventoryLevelExportTemplateTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var InventoryLevelExportTemplateTypeExtension */
    private $inventoryLevelExportTemplateTypeExtension;

    protected function setUp(): void
    {
        $this->inventoryLevelExportTemplateTypeExtension = new InventoryLevelExportTemplateTypeExtension();
    }

    public function testBuildFormShouldRemoveDefaultChild()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

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
            'oro_inventory.detailed_inventory_levels_template'
        ];

        $builder = $this->createMock(FormBuilderInterface::class);
        $phpunitTestCase = $this;

        $builder->expects($this->once())
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use ($phpunitTestCase, $processorAliases) {
                $choices = $options['choices'];
                $phpunitTestCase->assertContains(
                    $processorAliases[0],
                    $choices
                );
                $phpunitTestCase->assertContains(
                    $processorAliases[1],
                    $choices
                );
            });

        $this->inventoryLevelExportTemplateTypeExtension->buildForm(
            $builder,
            ['entityName' => InventoryLevel::class]
        );
    }
}
