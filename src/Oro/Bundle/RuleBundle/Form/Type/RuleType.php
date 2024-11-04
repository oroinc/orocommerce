<?php

namespace Oro\Bundle\RuleBundle\Form\Type;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Rule entity.
 */
class RuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_rule';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nameOptions = ['label' => 'oro.rule.name.label'];
        if ($options['name_tooltip']) {
            $nameOptions['tooltip'] = $options['name_tooltip'];
        }

        $enabledOptions = [
            'required' => false,
            'label' => 'oro.rule.enabled.label'
        ];
        if ($options['enabled_tooltip']) {
            $enabledOptions['tooltip'] = $options['enabled_tooltip'];
        }

        $sortOrderOptions = ['label' => 'oro.rule.sort_order.label'];
        if ($options['sortOrder_tooltip']) {
            $sortOrderOptions['tooltip'] = $options['sortOrder_tooltip'];
        }

        $stopProcessingOptions = [
            'required' => false,
            'label' => 'oro.rule.stop_processing.label',
        ];
        if ($options['stopProcessing_tooltip']) {
            $stopProcessingOptions['tooltip'] = $options['stopProcessing_tooltip'];
        }

        $builder
            ->add('name', TextType::class, $nameOptions)
            ->add('enabled', CheckboxType::class, $enabledOptions)
            ->add('sortOrder', IntegerType::class, $sortOrderOptions)
            ->add('stopProcessing', CheckboxType::class, $stopProcessingOptions)
            ->add('expression', TextareaType::class, [
                'label'    => 'oro.rule.expression.label',
                'required' => false,
            ])
        ;
    }

    /**
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
            'name_tooltip' => null,
            'enabled_tooltip' => null,
            'sortOrder_tooltip' => null,
            'stopProcessing_tooltip' => null
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
