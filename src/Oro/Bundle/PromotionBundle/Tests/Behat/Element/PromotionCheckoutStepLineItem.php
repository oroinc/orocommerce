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
        $discount = $this->find('xpath', '//table//td[text()="Row Discount:"]/following-sibling::td');

        return $discount ? $discount->getText() : null;
    }
}
