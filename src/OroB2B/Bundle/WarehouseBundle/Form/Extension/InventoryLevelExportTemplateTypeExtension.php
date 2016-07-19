<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelExportTemplateTypeExtension extends AbstractTypeExtension
{
    /**
     * @return array
     */
    public static function getProcessorAliases()
    {
        return [
            'orob2b_product.inventory_status_only_export_template' => 'orob2b.product.export.inventory_status_only',
            'orob2b_warehouse.inventory_level_export_template' => 'orob2b.warehouse.export.detailed_inventory_levels',
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
                'label' => 'orob2b.warehouse.export.popup.options.label',
                'choices' => $processorAliases,
                'required' => true,
                'placeholder' => false,
                'expanded' => true,
                'data' => $defaultAlias,
            ]
        );
    }
}
