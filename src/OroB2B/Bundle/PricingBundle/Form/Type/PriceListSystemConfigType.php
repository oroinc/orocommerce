<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\PricingBundle\Model\PriceListConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
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
            ->add('priceList', PriceListSelectWithPriorityType::NAME, []);
//            ->add('priceLists', 'oro_collection', [
//                'label' => false,
//                'type' => PriceListSelectWithPriorityType::NAME,
//                'options' => [
//                    'error_bubbling' => false,
//                ],
//                'handle_primary' => false,
//                'allow_add_after' => true,
//            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
            'label' => false,
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
