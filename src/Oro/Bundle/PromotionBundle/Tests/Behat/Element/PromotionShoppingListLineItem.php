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
        $discount = $this->find(
            'xpath',
            '//td[contains(@class, "grid-body-cell-subtotal")]//div[contains(@data-label, "Discount")]'
        );

        return $discount ? $discount->getText() : null;
    }
}
