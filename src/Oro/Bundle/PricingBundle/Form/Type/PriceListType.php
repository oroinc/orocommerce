<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class PriceListType extends AbstractType
{
    const NAME = 'oro_pricing_price_list';
    const SCHEDULES_FIELD = 'schedules';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceList $priceList */
        $priceList = $builder->getData();

        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'oro.pricing.pricelist.name.label'])
            ->add(
                self::SCHEDULES_FIELD,
                CollectionType::NAME,
                [
                    'type' => PriceListScheduleType::NAME,
                    'by_reference' => false,
                    'required' => false,
                ]
            )
            ->add(
                'currencies',
                CurrencySelectionType::NAME,
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'oro.pricing.pricelist.currencies.label',
                    'additional_currencies' => $priceList ? $priceList->getCurrencies() : [],
                ]
            )
            ->add(
                'active',
                'checkbox',
                [
                    'label' => 'oro.pricing.pricelist.active.label'
                ]
            )
            ->add(
                'productAssignmentRule',
                'textarea',
                [
                    'label' => 'oro.pricing.pricelist.product_assignment_rule.label',
                    'required' => false
                ]
            )
            ->add(
                'priceRules',
                CollectionType::NAME,
                [
                    'type' => PriceRuleType::NAME,
                    'label' => false,
                    'required' => false,
                    'by_reference' => false,
                    'delete_empty' => true
                ]
            );

        $builder->add(
            'test',
            PageSelectType::class,
            [
                'mapped' => false,
                'label' => 'Landing Page'
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
                'data_class' => PriceList::class
            ]
        );
    }

    /**
     * @return string
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
