<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Shipping methods configs form
 */
class ShippingMethodsConfigsRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_methods_configs_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule', RuleType::class, ['label' => 'oro.shipping.shippingmethodsconfigsrule.rule.label'])
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.shipping.shippingmethodsconfigsrule.currency.label',
                'placeholder' => 'oro.currency.currency.form.choose',
            ])
            ->add('destinations', CollectionType::class, [
                'required'             => false,
                'entry_type'           => ShippingMethodsConfigsRuleDestinationType::class,
                'label'                => 'oro.shipping.shippingmethodsconfigsrule.destinations.label',
                'show_form_when_empty' => false,
            ])
            ->add('methodConfigs', ShippingMethodConfigCollectionType::class)
            ->add('method', ShippingMethodSelectType::class, [
                'label' => 'oro.shipping.shippingmethodsconfigsrule.method.label',
                'mapped' => false,
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingMethodsConfigsRule::class,
            'validation_groups' => [Constraint::DEFAULT_GROUP, 'ShippingMethodsConfigsRule'],
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
