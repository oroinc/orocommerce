<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;

class DiscountStub extends AbstractDiscount
{
    #[\Override]
    public function apply(DiscountContextInterface $discountContext)
    {
    }

    #[\Override]
    public function calculate($entity): float
    {
        return 0.0;
    }
}
