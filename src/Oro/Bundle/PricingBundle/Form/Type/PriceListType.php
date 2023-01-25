<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for PriceList entity
 */
class PriceListType extends AbstractType
{
    const NAME = 'oro_pricing_price_list';
    const SCHEDULES_FIELD = 'schedules';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var PriceList $priceList */
        $priceList = $builder->getData();

        $builder
            ->add('name', TextType::class, ['required' => true, 'label' => 'oro.pricing.pricelist.name.label'])
            ->add(
                self::SCHEDULES_FIELD,
                ScheduleIntervalsCollectionType::class,
                [
                    'entry_options' => [
                        'data_class' => PriceListSchedule::class
                    ]
                ]
            )
            ->add(
                'currencies',
                CurrencySelectionType::class,
                [
                    'multiple' => true,
                    'required' => true,
                    'full_currency_name' => true,
                    'label' => 'oro.pricing.pricelist.currencies.label',
                    'additional_currencies' => $priceList ? $priceList->getCurrencies() : [],
                ]
            )
            ->add(
                'active',
                CheckboxType::class,
                [
                    'label' => 'oro.pricing.pricelist.active.label'
                ]
            )
            ->add(
                'productAssignmentRule',
                PriceRuleEditorType::class,
                [
                    'label' => 'oro.pricing.pricelist.product_assignment_rule.label',
                    'required' => false
                ]
            )
            ->add(
                'priceRules',
                CollectionType::class,
                [
                    'entry_type' => PriceRuleType::class,
                    'label' => false,
                    'required' => false,
                    'by_reference' => false,
                    'delete_empty' => true
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
