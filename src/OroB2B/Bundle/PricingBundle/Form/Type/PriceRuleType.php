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

    const RULE = 'rule';
    const RULE_CONDITION = 'ruleCondition';
    const CURRENCY = 'currency';
    const PRODUCT_UNIT = 'productUnit';
    const QUANTITY = 'quantity';
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
                    'label' => 'orob2b.pricing.pricerule.rule.label'
                ]
            )
            ->add(
                self::RULE_CONDITION,
                'textarea',
                [
                    'label' => 'orob2b.pricing.pricerule.rule_condition.label',
                    'required' => false
                ]
            )
            ->add(
                self::CURRENCY,
                CurrencySelectionType::NAME,
                [
                    'label' => 'orob2b.pricing.pricerule.rule_condition.label'
                ]
            )
            ->add(
                self::PRODUCT_UNIT,
                'entity',
                [
                    'class' => ProductUnit::class,
                    'label' => 'orob2b.pricing.pricerule.product_unit.label'
                ]
            )
            ->add(
                self::QUANTITY,
                'text',
                [
                    'label' => 'orob2b.pricing.pricerule.quantity.label'
                ]
            )
            ->add(
                self::PRIORITY,
                'integer',
                [
                    'label' => 'orob2b.pricing.pricerule.priority.label'
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
