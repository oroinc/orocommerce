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

class RuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $nameOptions = ['label' => 'oro.rule.name.label'];
        if ($options['name_tooltip']) {
            $nameOptions['tooltip'] = $options['name_tooltip'];
        }

        $builder
            ->add('name', TextType::class, $nameOptions)
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'oro.rule.enabled.label'
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'oro.rule.sort_order.label'
            ])
            ->add('stopProcessing', CheckboxType::class, [
                'required' => false,
                'label' => 'oro.rule.stop_processing.label',
            ])
            ->add('expression', TextareaType::class, [
                'label'    => 'oro.rule.expression.label',
                'required' => false,
            ])
        ;
    }

    /**
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
            'name_tooltip' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
