<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodsConfigsRuleType extends AbstractType
{
    const NAME = 'oro_payment_methods_configs_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('methodConfigs', PaymentMethodConfigCollectionType::class, [
                'required' => false,
                'label' => 'oro.payment.paymentmethodsconfigsrule.method_configs.label',
            ])
            ->add('destinations', PaymentMethodsConfigsRuleDestinationCollectionType::class, [
                'required' => false,
                'label' => 'oro.payment.paymentmethodsconfigsrule.destinations.label',
            ])
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.payment.paymentmethodsconfigsrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('rule', RuleType::class, [
                'label' => 'oro.payment.paymentmethodsconfigsrule.rule.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodsConfigsRule::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
