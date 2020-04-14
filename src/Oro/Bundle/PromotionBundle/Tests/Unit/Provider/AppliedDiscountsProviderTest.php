<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedDiscountsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AppliedDiscountsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new AppliedDiscountsProvider();
    }

    public function testGetDiscountsAmountByOrder()
    {
        $order = new Order();
        $order->addAppliedPromotion($this->createAppliedPromotion('some type', [2, 3, 5]));
        $order->addAppliedPromotion($this->createAppliedPromotion(ShippingDiscount::NAME, [33, 66]));

        $amount = $this->provider->getDiscountsAmountByOrder($order);
        $this->assertEquals(10, $amount);
    }

    public function testGetShippingDiscountsAmountByOrder()
    {
        $order = new Order();
        $order->addAppliedPromotion($this->createAppliedPromotion('some type', [2, 3, 5]));
        $order->addAppliedPromotion($this->createAppliedPromotion(ShippingDiscount::NAME, [33, 66]));

        $amount = $this->provider->getShippingDiscountsAmountByOrder($order);
        $this->assertEquals(99, $amount);
    }

    public function testGetDiscountsAmountByLineItem()
    {
        $order = new Order();
        $lineItem = new OrderLineItem();
        $lineItem->setOrder($order);
        $someOtherLineItem = new OrderLineItem();
        $someOtherLineItem->setOrder($order);
        $order->addAppliedPromotion($this->createAppliedPromotion('some type', [2, 3, 5], $lineItem));
        $order->addAppliedPromotion($this->createAppliedPromotion('some type', [2, 3, 5], $someOtherLineItem));
        $order->addAppliedPromotion($this->createAppliedPromotion(ShippingDiscount::NAME, [33, 66], $lineItem));

        $amount = $this->provider->getDiscountsAmountByLineItem($lineItem);
        $this->assertEquals(10, $amount);
    }

    /**
     * @param string $type
     * @param array $discountAmounts
     * @param OrderLineItem|null $lineItem
     * @return AppliedPromotion
     */
    private function createAppliedPromotion(string $type, array $discountAmounts, OrderLineItem $lineItem = null)
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setType($type);
        foreach ($discountAmounts as $discountAmount) {
            $appliedDiscount = new AppliedDiscount();
            $appliedDiscount->setAmount($discountAmount);
            $appliedPromotion->addAppliedDiscount($appliedDiscount);
            $appliedDiscount->setLineItem($lineItem);
        }

        return $appliedPromotion;
    }
}
