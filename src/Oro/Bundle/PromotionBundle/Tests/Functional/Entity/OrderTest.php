<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrderTest extends WebTestCase
{
    use EntityTestCaseTrait;

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
