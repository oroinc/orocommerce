<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscountFreeShippingType extends AbstractType
{
    const NAME = 'oro_promotion_discount_free_shipping_choice';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.promotion.discount.free_shipping.label',
            'empty_data' => null,
            'empty_value' => 'oro.promotion.discount.free_shipping.no',
            'required' => false,
            'choices' => [
                ShippingDiscount::APPLY_TO_ITEMS => 'oro.promotion.discount.free_shipping.for_matching_items_only',
                ShippingDiscount::APPLY_TO_ORDER =>
                    'oro.promotion.discount.free_shipping.for_shipment_with_matching_items'
            ],
        ]);
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
