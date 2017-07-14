<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;

class PromotionCheckoutStep extends CheckoutStep
{
    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('PromotionCheckoutStepLineItem');
    }
}
