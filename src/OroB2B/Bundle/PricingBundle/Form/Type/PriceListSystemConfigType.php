<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PriceListSystemConfigType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_system_config';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('configs', 'oro_collection', [
                'label' => false,
                'type' => PriceListSelectWithPriorityType::NAME,
                'options' => [
                    'data_class' => PriceListConfig::CLASS_NAME,
                    'error_bubbling' => false,
                ],
                'handle_primary' => false,
                'allow_add_after' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
            'label' => false,
            'data_class' => PriceListConfigBag::CLASS_NAME,
            'error_bubbling' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
