<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for configuration, calculation and applying shipping discount.
 */
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

        $shippingCost = $entity->getShippingCost();
        if (null === $shippingCost) {
            return 0.0;
        }
        /**
         * by some unknown reason getShippingCost() can return Price, null or float, as example
         * {@see DiscountContextInterface::getShippingCost()}, its return type is float instead of expected Price|null
         */
        if ($shippingCost instanceof Price) {
            $shippingCost = $shippingCost->getValue();
        }

        return $this->calculateDiscountAmount($shippingCost);
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
