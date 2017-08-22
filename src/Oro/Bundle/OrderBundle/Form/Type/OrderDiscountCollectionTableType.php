<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderDiscountCollectionTableType extends AbstractType
{
    const NAME = 'oro_order_discount_collection_table';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OrderCollectionTableType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'template_name' => 'OroOrderBundle:Form:order_discount_collection.html.twig',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'oroorder/js/app/views/discount-items-view',
                    'discountType' => DiscountSubtotalProvider::TYPE,
                    'totalType' => LineItemSubtotalProvider::TYPE,
                ],
                'attr' => ['class' => 'oro-discount-collection'],
                'entry_type' => OrderDiscountCollectionRowType::NAME
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
