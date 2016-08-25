<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextareaType::class, ['label' => 'oro.shipping.shippingrule.name.label'])
            ->add('enabled', CheckboxType::class, ['label' => 'oro.shipping.shippingrule.enabled.label'])
            ->add('priority', NumberType::class, ['label' => 'oro.shipping.shippingrule.priority.label'])
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.shipping.shippingrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('destinations', CollectionType::class, [
                'required' => false,
                'entry_type' => ShippingRuleDestinationType::class,
                'label' => 'oro.shipping.shippingrule.shipping_destinations.label',
            ])
            ->add('conditions', TextareaType::class, [
                'required' => false,
                'label' => 'oro.shipping.shippingrule.conditions.label',
            ])
            ->add('methodConfigs', CollectionType::class, [
                'required' => false,
                'entry_type' => ShippingRuleMethodConfigType::class,
            ])
            ->add('stopProcessing', CheckboxType::class, [
                'required' => false,
                'label' => 'oro.shipping.shippingrule.stop_processing.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRule::class,
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
