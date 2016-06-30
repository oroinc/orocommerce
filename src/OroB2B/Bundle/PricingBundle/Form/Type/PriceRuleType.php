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
     *

     price for qty 10 of each in USD = xxxxx
    [19:35:33] Michael Bessolov: Price for quantity [____] [each|V] in [USD|v]
    Calculate as;    [____________________]
    Only when:    [_____________________]
    Priority:     [_______]

     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'rule',
                'textarea',
                [
                    'label' => 'orob2b.pricing.pricerule.rule.label'
                ]
            )
            ->add(
                'ruleCondition',
                'textarea',
                [
                    'label' => 'orob2b.pricing.pricerule.rule_condition.label'
                ]
            )
            ->add(
                'currency',
                CurrencySelectionType::NAME,
                [
                    'label' => 'orob2b.pricing.pricerule.rule_condition.label'
                ]
            )
            ->add(
                'productUnit',
                'entity',
                [
                    'class' => ProductUnit::class,
                    'label' => 'orob2b.pricing.pricerule.product_unit.label'
                ]
            )
            ->add(
                'quantity',
                'number',
                [
                    'label' => 'orob2b.pricing.pricerule.quantity.label'
                ]
            )
            ->add(
                'priority',
                'number',
                [
                    'label' => 'orob2b.pricing.pricerule.quantity.label'
                ]
            );
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
