<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;

class PriceListScheduleType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_schedule';
    const ACTIVE_AT_FIELD = 'activeAt';
    const DEACTIVATE_AT_FIELD = 'deactivateAt';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::ACTIVE_AT_FIELD, OroDateTimeType::NAME, [
                'required' => false
            ])
            ->add(self::DEACTIVATE_AT_FIELD, OroDateTimeType::NAME, [
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule',
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
