<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStepLineItem;

class PromotionCheckoutStepLineItem extends CheckoutStepLineItem implements DiscountAwareLineItemInterface
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
