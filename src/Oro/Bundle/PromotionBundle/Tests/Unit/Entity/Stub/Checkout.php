<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout as BaseCheckout;

class Checkout extends BaseCheckout
{
    use AppliedCouponsTrait;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
    }
}
