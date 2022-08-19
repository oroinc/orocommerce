<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testAppliedCouponsCollection()
    {
        $order = new Order();
        $firstCoupon = new AppliedCoupon();
        $secondCoupon = new AppliedCoupon();

        $order->addAppliedCoupon($firstCoupon);
        $order->addAppliedCoupon($secondCoupon);

        static::assertEquals([$firstCoupon, $secondCoupon], iterator_to_array($order->getAppliedCoupons()));
        $order->removeAppliedCoupon($secondCoupon);

        static::assertEquals([$firstCoupon], iterator_to_array($order->getAppliedCoupons()));
    }
}
