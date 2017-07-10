<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStepLineItem;

class PromotionOrderLineItem extends CheckoutStepLineItem implements DiscountAwareLineItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDiscount()
    {
        $discount = $this->find('css', '.grid-body-cell-rowTotalDiscountAmount');

        return $discount ? $discount->getText() : null;
    }
}
