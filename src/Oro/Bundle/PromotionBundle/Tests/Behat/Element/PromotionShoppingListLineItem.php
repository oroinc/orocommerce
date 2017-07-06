<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingListLineItem;

class PromotionShoppingListLineItem extends ShoppingListLineItem implements DiscountAwareLineItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDiscount()
    {
        $discount = $this->find('css', 'span[data-name="discount-value"]');

        return $discount ? $discount->getText() : null;
    }
}
