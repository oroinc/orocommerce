<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for order discount options.
 *
 * Extends {@see DiscountOptionsType} to provide form configuration for order-level discounts
 * that apply to the entire order subtotal.
 */
class OrderDiscountOptionsType extends AbstractType
{
    const NAME = 'oro_promotion_order_discount_options';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DiscountOptionsType::class;
    }
}
