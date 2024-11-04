<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

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
