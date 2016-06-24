<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;

class InventoryLevelExportTemplateTypeExtension extends AbstractTypeExtension
{
    const NAME = 'orob2b_inventory_level_export_template_type_extension';

    /** @var string[] */
    public static $processorAliases = [
        'orob2b_warehouse.inventory_status_only_export_template',
        'orob2b_warehouse.inventory_level_export_template'
    ];

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
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
        $processorAliases = self::$processorAliases;
        $defaultChoice = reset($processorAliases);
        $builder->remove(ExportTemplateType::CHILD_PROCESSOR_ALIAS);

        $builder->add(
            'detailLevel',
            'choice',
            [
                'label' => false,
                'choices' => [
                    $this->getExportDetailLevelsByProcessorAliases($processorAliases)
                ],
                'choices_as_values' => false,
                'choice_translation_domain' => true,
                'mapped' => false,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new NotBlank()
                ],
                'data' => $defaultChoice,
                'required' => true
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $processorAlias = $event->getForm()->get('detailLevel')->getData();
            $event->getForm()->getData()->setProcessorAlias($processorAlias);
        });
    }

    /**
     * @param array $processorAliases
     * @return array
     */
    protected function getExportDetailLevelsByProcessorAliases($processorAliases)
    {
        $choices = [];
        foreach ($processorAliases as $alias) {
            $choices[$alias] = $this->getTranslationLabel($alias);
        }

        return $choices;
    }

    /**
     * @param string $label
     * @return string
     */
    protected function getTranslationLabel($label)
    {
        return 'orob2b.warehouse.export.inventory_status.' . $label;
    }
}
