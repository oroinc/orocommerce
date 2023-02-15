<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a discount to be created to the order entity this discount belongs to.
 * This processor is required because OrderDiscount::setOrder()
 * does not add the discount to the order, as result the response
 * of the create discount action does not contains this discount in the included order
 * and the order totals are calculated without this discount.
 */
class AddDiscountToOrder implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderDiscount $discount */
        $discount = $context->getData();
        $order = $discount->getOrder();
        if (null !== $order) {
            $discounts = $order->getDiscounts();
            if (!$discounts->contains($discount)) {
                $discounts->add($discount);
            }
        }
    }
}
