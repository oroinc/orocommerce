<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceRuleType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_rule';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule', 'text', [])
            ->add('ruleCondition', 'text', [])
            ->add(
                'currency',
                CurrencySelectionType::NAME,
                []
            )->add(
                'productUnit',
                'entity',
                [
                    'class' => ProductUnit::class,
                    'label' => 'orob2b.pricing.unit.label'
                ]
            )->add(
                'quantity',
                'number',
                [
                    'label' => 'orob2b.pricing.quantity.label'
                ]
            )->add('priority', 'number', []);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => PriceRule::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
