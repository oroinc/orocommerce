<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\OrderLineItem;

class PromotionOrderLineItem extends OrderLineItem implements DiscountAwareLineItemInterface
{
    #[\Override]
    public function getDiscount()
    {
        $discount = $this->find('css', '.grid-body-cell-rowTotalDiscountAmount');

        return $discount ? $discount->getText() : null;
    }
}
