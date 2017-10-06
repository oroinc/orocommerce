<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout as BaseCheckout;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;

class Checkout extends BaseCheckout implements AppliedCouponsAwareInterface
{
    use AppliedCouponsTrait;

    public function __construct()
    {
        parent::__construct();

        $this->appliedCoupons = new ArrayCollection();
    }
}
