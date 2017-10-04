<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingDiscount extends AbstractDiscount
{
    const NAME = 'shipping';
    const SHIPPING_OPTIONS = 'shipping_options';
    const SHIPPING_METHOD = 'shipping_method';
    const SHIPPING_METHOD_TYPE = 'shipping_method_type';

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContextInterface $discountContext)
    {
        $discountContext->addShippingDiscount($this);
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        if (!$entity instanceof ShippingAwareInterface) {
            return 0.0;
        }

        return $this->calculateDiscountAmount($entity->getShippingCost());
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = parent::getOptionsResolver();

        $resolver->setDefined(self::SHIPPING_OPTIONS);
        $resolver->setAllowedTypes(self::SHIPPING_OPTIONS, 'array');

        return $resolver;
    }
}
