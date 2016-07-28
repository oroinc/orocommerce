<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingRuleType extends AbstractType
{
    const NAME = 'orob2b_shipping_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'textarea', ['label' => 'orob2b.shipping.shippingrule.name.label'])
            ->add('enabled', 'checkbox', ['label' => 'orob2b.shipping.shippingrule.enabled.label'])
            ->add('priority', 'number', ['label' => 'orob2b.shipping.shippingrule.priority.label'])
            ->add('currency', CurrencySelectionType::NAME, [
                'label' => 'orob2b.shipping.shippingrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('shippingDestinations', CollectionType::NAME, [
                'required' => false,
                'type' => ShippingRuleDestinationType::NAME,
                'label' => 'orob2b.shipping.shippingrule.shipping_destinations.label',
            ])
            ->add('conditions', 'textarea', [
                'required' => false,
                'label' => 'orob2b.shipping.shippingrule.conditions.label',
            ])
            ->add('configurations', ShippingRuleConfigurationCollectionType::NAME, [
                'required' => false,
                'type' => ShippingRuleConfigurationType::NAME,
                'label' => 'orob2b.shipping.shippingrule.configurations.label',
            ])
            ->add('stopProcessing', 'checkbox', [
                'required' => false,
                'label' => 'orob2b.shipping.shippingrule.stopProcessing.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRule::class,
            'cascade_validation' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
