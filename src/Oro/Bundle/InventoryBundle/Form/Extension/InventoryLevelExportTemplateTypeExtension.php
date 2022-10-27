<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class InventoryLevelExportTemplateTypeExtension extends AbstractTypeExtension
{
    /**
     * @return array
     */
    public static function getProcessorAliases()
    {
        return [
            'oro_product.inventory_status_only_template' => 'oro.product.export.inventory_status_only',
            'oro_inventory.detailed_inventory_levels_template' => 'oro.inventory.export.detailed_inventory_levels',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ExportTemplateType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(isset($options['entityName']) && $options['entityName'] === InventoryLevel::class)) {
            return;
        }

        $processorAliases = static::getProcessorAliases();
        $defaultAlias = array_keys($processorAliases)[0];

        $builder->remove('processorAlias');
        $builder->add(
            'processorAlias',
            ChoiceType::class,
            [
                'label' => 'oro.inventory.export.popup.options.label',
                'choices' => array_flip($processorAliases),
                'required' => true,
                'placeholder' => false,
                'expanded' => true,
                'data' => $defaultAlias,
            ]
        );
    }
}
