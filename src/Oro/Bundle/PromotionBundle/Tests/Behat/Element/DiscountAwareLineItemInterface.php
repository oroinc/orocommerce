<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

interface DiscountAwareLineItemInterface
{
    /**
     * @return string
     */
    public function getDiscount();
}
