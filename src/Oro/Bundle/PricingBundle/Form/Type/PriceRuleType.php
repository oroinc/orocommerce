<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceRuleType extends AbstractType
{
    const NAME = 'oro_pricing_price_rule';

    const RULE = 'rule';
    const RULE_CONDITION = 'ruleCondition';
    const CURRENCY = 'currency';
    const CURRENCY_EXPRESSION = 'currencyExpression';
    const PRODUCT_UNIT = 'productUnit';
    const PRODUCT_UNIT_EXPRESSION = 'productUnitExpression';
    const QUANTITY = 'quantity';
    const QUANTITY_EXPRESSION = 'quantityExpression';
    const PRIORITY = 'priority';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::RULE,
                'textarea',
                [
                    'label' => 'oro.pricing.pricerule.calculate_as.label'
                ]
            )
            ->add(
                self::RULE_CONDITION,
                'textarea',
                [
                    'label' => 'oro.pricing.pricerule.rule_condition.label',
                    'required' => false
                ]
            )
            ->add(
                self::CURRENCY,
                CurrencySelectionType::NAME,
                [
                    'label' => 'oro.pricing.pricerule.currency.label'
                ]
            )
            ->add(
                self::PRODUCT_UNIT,
                'entity',
                [
                    'class' => ProductUnit::class,
                    'label' => 'oro.pricing.pricerule.product_unit.label'
                ]
            )
            ->add(
                self::QUANTITY,
                'text',
                [
                    'label' => 'oro.pricing.pricerule.quantity.label'
                ]
            )
            ->add(
                self::PRIORITY,
                'integer',
                [
                    'label' => 'oro.pricing.pricerule.priority.label'
                ]
            )
            ->add(
                self::QUANTITY_EXPRESSION,
                'text',
                [
                    'label' => 'oro.pricing.pricerule.quantity_expression.label',
                    'attr' => array('placeholder' => 'oro.pricing.pricerule.quantity.label')
                ]
            )
            ->add(
                self::CURRENCY_EXPRESSION,
                'text',
                [
                    'label' => 'oro.pricing.pricerule.currency_expression.label',
                    'attr' => array('placeholder' => 'oro.pricing.pricerule.currency.label')
                ]
            )
            ->add(
                self::PRODUCT_UNIT_EXPRESSION,
                'text',
                [
                    'label' => 'oro.pricing.pricerule.product_unit_expression.label',
                    'attr' => array('placeholder' => 'oro.pricing.pricerule.product_unit.label')
                ]
            )
        ;
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
