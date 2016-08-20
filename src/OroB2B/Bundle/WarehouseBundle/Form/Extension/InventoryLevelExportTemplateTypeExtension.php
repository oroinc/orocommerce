<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelExportTemplateTypeExtension extends AbstractTypeExtension
{
    /**
     * @return array
     */
    public static function getProcessorAliases()
    {
        return [
            'orob2b_product.inventory_status_only_template'
                => 'oro.product.export.inventory_status_only',
            'orob2b_warehouse.detailed_inventory_levels_template'
                => 'oro.warehouse.export.detailed_inventory_levels',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ExportTemplateType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(isset($options['entityName']) && $options['entityName'] === WarehouseInventoryLevel::class)) {
            return;
        }

        $processorAliases = static::getProcessorAliases();
        $defaultAlias = array_keys($processorAliases)[0];

        $builder->remove('processorAlias');
        $builder->add(
            'processorAlias',
            'choice',
            [
                'label' => 'oro.warehouse.export.popup.options.label',
                'choices' => $processorAliases,
                'required' => true,
                'placeholder' => false,
                'expanded' => true,
                'data' => $defaultAlias,
            ]
        );
    }
}
