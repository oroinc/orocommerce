<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;

class PromotionCheckoutStep extends CheckoutStep implements DiscountSubtotalAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDiscountSubtotal()
    {
        return $this->getElement('PromotionCheckoutStepDiscountSubtotal')->getText();
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('PromotionCheckoutStepLineItem');
    }
}
