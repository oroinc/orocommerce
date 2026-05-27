<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderDuplicateAfterEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\EventListener\OrderDuplicateAfterEventListener;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class OrderDuplicateAfterEventListenerTest extends TestCase
{
    private OrderDuplicateAfterEventListener $listener;

    protected function setUp(): void
    {
        $this->listener = new OrderDuplicateAfterEventListener();
    }

    public function testOnOrderDuplicateAfterWithNoAppliedPromotions(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        self::assertCount(0, $order->getAppliedPromotions());
        self::assertCount(0, $duplicatedOrder->getAppliedPromotions());
        self::assertCount(0, $duplicatedOrder->getAppliedCoupons());
    }

    public function testOnOrderDuplicateAfterCopiesAllPromotionFields(): void
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setActive(true);
        $appliedPromotion->setRemoved(false);
        $appliedPromotion->setType('order');
        $appliedPromotion->setSourcePromotionId(100);
        $appliedPromotion->setPromotionName('Spring Sale 2026');
        $appliedPromotion->setConfigOptions(['discount_type' => 'percentage', 'value' => 15]);
        $appliedPromotion->setPromotionData(['conditions' => ['min_amount' => 100], 'actions' => ['discount' => 15]]);

        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->addAppliedPromotion($appliedPromotion);

        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        self::assertCount(1, $duplicatedOrder->getAppliedPromotions());

        $clonedPromotion = $duplicatedOrder->getAppliedPromotions()->first();
        self::assertNotSame($appliedPromotion, $clonedPromotion);

        self::assertTrue($clonedPromotion->isActive());
        self::assertFalse($clonedPromotion->isRemoved());
        self::assertEquals('order', $clonedPromotion->getType());
        self::assertEquals(100, $clonedPromotion->getSourcePromotionId());
        self::assertEquals('Spring Sale 2026', $clonedPromotion->getPromotionName());
        self::assertEquals(['discount_type' => 'percentage', 'value' => 15], $clonedPromotion->getConfigOptions());
        self::assertEquals(
            ['conditions' => ['min_amount' => 100], 'actions' => ['discount' => 15]],
            $clonedPromotion->getPromotionData()
        );
        self::assertSame($duplicatedOrder, $clonedPromotion->getOrder());
    }

    public function testOnOrderDuplicateAfterClonesAppliedDiscounts(): void
    {
        $appliedDiscount1 = new AppliedDiscount();
        $appliedDiscount1->setAmount(10.50);
        $appliedDiscount1->setCurrency('USD');

        $appliedDiscount2 = new AppliedDiscount();
        $appliedDiscount2->setAmount(25.00);
        $appliedDiscount2->setCurrency('EUR');

        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setSourcePromotionId(100);
        $appliedPromotion->setType('order');
        $appliedPromotion->setPromotionName('Multi Discount Promotion');
        $appliedPromotion->addAppliedDiscount($appliedDiscount1);
        $appliedPromotion->addAppliedDiscount($appliedDiscount2);

        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->addAppliedPromotion($appliedPromotion);

        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        self::assertCount(1, $duplicatedOrder->getAppliedPromotions());

        $clonedPromotion = $duplicatedOrder->getAppliedPromotions()->first();
        self::assertCount(2, $clonedPromotion->getAppliedDiscounts());

        $clonedDiscounts = $clonedPromotion->getAppliedDiscounts()->toArray();

        self::assertNotSame($appliedDiscount1, $clonedDiscounts[0]);
        self::assertEquals(10.50, $clonedDiscounts[0]->getAmount());
        self::assertEquals('USD', $clonedDiscounts[0]->getCurrency());
        self::assertNull($clonedDiscounts[0]->getLineItem());

        self::assertNotSame($appliedDiscount2, $clonedDiscounts[1]);
        self::assertEquals(25.00, $clonedDiscounts[1]->getAmount());
        self::assertEquals('EUR', $clonedDiscounts[1]->getCurrency());
        self::assertNull($clonedDiscounts[1]->getLineItem());
    }

    public function testOnOrderDuplicateAfterWhenHasLineItem(): void
    {
        $sourceLineItem1 = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem1, 100);
        $sourceLineItem1->setProductSku('SKU-001');

        $sourceLineItem2 = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem2, 200);
        $sourceLineItem2->setProductSku('SKU-002');

        $duplicatedLineItem1 = new OrderLineItem();
        ReflectionUtil::setId($duplicatedLineItem1, 300);
        $duplicatedLineItem1->setProductSku('SKU-001');

        $duplicatedLineItem2 = new OrderLineItem();
        ReflectionUtil::setId($duplicatedLineItem2, 400);
        $duplicatedLineItem2->setProductSku('SKU-002');

        $appliedDiscount1 = new AppliedDiscount();
        $appliedDiscount1->setAmount(15.00);
        $appliedDiscount1->setCurrency('USD');
        $appliedDiscount1->setLineItem($sourceLineItem1);

        $appliedDiscount2 = new AppliedDiscount();
        $appliedDiscount2->setAmount(20.00);
        $appliedDiscount2->setCurrency('EUR');
        $appliedDiscount2->setLineItem($sourceLineItem2);

        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setSourcePromotionId(100);
        $appliedPromotion->setType('line_item');
        $appliedPromotion->setPromotionName('Line Item Discount');
        $appliedPromotion->addAppliedDiscount($appliedDiscount1);
        $appliedPromotion->addAppliedDiscount($appliedDiscount2);

        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->addLineItem($sourceLineItem1);
        $order->addLineItem($sourceLineItem2);
        $order->addAppliedPromotion($appliedPromotion);

        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);
        $duplicatedOrder->addLineItem($duplicatedLineItem1);
        $duplicatedOrder->addLineItem($duplicatedLineItem2);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        self::assertCount(1, $duplicatedOrder->getAppliedPromotions());

        $clonedPromotion = $duplicatedOrder->getAppliedPromotions()->first();
        self::assertCount(2, $clonedPromotion->getAppliedDiscounts());

        $clonedDiscounts = $clonedPromotion->getAppliedDiscounts()->toArray();

        self::assertNotSame($appliedDiscount1, $clonedDiscounts[0]);
        self::assertEquals(15.00, $clonedDiscounts[0]->getAmount());
        self::assertEquals('USD', $clonedDiscounts[0]->getCurrency());
        self::assertSame($duplicatedLineItem1, $clonedDiscounts[0]->getLineItem());
        self::assertNotSame($sourceLineItem1, $clonedDiscounts[0]->getLineItem());

        self::assertNotSame($appliedDiscount2, $clonedDiscounts[1]);
        self::assertEquals(20.00, $clonedDiscounts[1]->getAmount());
        self::assertEquals('EUR', $clonedDiscounts[1]->getCurrency());
        self::assertSame($duplicatedLineItem2, $clonedDiscounts[1]->getLineItem());
        self::assertNotSame($sourceLineItem2, $clonedDiscounts[1]->getLineItem());
    }

    public function testOnOrderDuplicateAfterCopiesAllCouponFields(): void
    {
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('SUMMER2026');
        $appliedCoupon->setSourcePromotionId(100);
        $appliedCoupon->setSourceCouponId(200);

        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setSourcePromotionId(100);
        $appliedPromotion->setType('order');
        $appliedPromotion->setPromotionName('Coupon Promotion');
        $appliedPromotion->setAppliedCoupon($appliedCoupon);

        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->addAppliedPromotion($appliedPromotion);

        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        self::assertCount(1, $duplicatedOrder->getAppliedPromotions());

        $clonedPromotion = $duplicatedOrder->getAppliedPromotions()->first();
        self::assertNotNull($clonedPromotion->getAppliedCoupon());

        $clonedCoupon = $clonedPromotion->getAppliedCoupon();
        self::assertNotSame($appliedCoupon, $clonedCoupon);
        self::assertEquals('SUMMER2026', $clonedCoupon->getCouponCode());
        self::assertEquals(100, $clonedCoupon->getSourcePromotionId());
        self::assertEquals(200, $clonedCoupon->getSourceCouponId());
        self::assertSame($duplicatedOrder, $clonedCoupon->getOrder());

        self::assertCount(1, $duplicatedOrder->getAppliedCoupons());
        self::assertTrue($duplicatedOrder->getAppliedCoupons()->contains($clonedCoupon));
    }

    public function testOnOrderDuplicateAfterWithComplexScenario(): void
    {
        // Create line items
        $sourceLineItem1 = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem1, 100);
        $sourceLineItem1->setProductSku('SKU-001');

        $sourceLineItem2 = new OrderLineItem();
        ReflectionUtil::setId($sourceLineItem2, 200);
        $sourceLineItem2->setProductSku('SKU-002');

        $duplicatedLineItem1 = new OrderLineItem();
        ReflectionUtil::setId($duplicatedLineItem1, 300);
        $duplicatedLineItem1->setProductSku('SKU-001');

        $duplicatedLineItem2 = new OrderLineItem();
        ReflectionUtil::setId($duplicatedLineItem2, 400);
        $duplicatedLineItem2->setProductSku('SKU-002');

        // Create first promotion with coupon and line item discounts
        $coupon1 = new AppliedCoupon();
        $coupon1->setCouponCode('SAVE10');
        $coupon1->setSourcePromotionId(100);
        $coupon1->setSourceCouponId(500);

        $discount1 = new AppliedDiscount();
        $discount1->setAmount(10.00);
        $discount1->setCurrency('USD');
        $discount1->setLineItem($sourceLineItem1);

        $discount2 = new AppliedDiscount();
        $discount2->setAmount(5.00);
        $discount2->setCurrency('USD');
        $discount2->setLineItem($sourceLineItem2);

        $promotion1 = new AppliedPromotion();
        $promotion1->setActive(true);
        $promotion1->setRemoved(false);
        $promotion1->setType('line_item');
        $promotion1->setSourcePromotionId(100);
        $promotion1->setPromotionName('Item Discount with Coupon');
        $promotion1->setConfigOptions(['type' => 'percentage']);
        $promotion1->setPromotionData(['discount' => 10]);
        $promotion1->setAppliedCoupon($coupon1);
        $promotion1->addAppliedDiscount($discount1);
        $promotion1->addAppliedDiscount($discount2);

        // Create second promotion without coupon and with order-level discount
        $discount3 = new AppliedDiscount();
        $discount3->setAmount(20.00);
        $discount3->setCurrency('EUR');

        $promotion2 = new AppliedPromotion();
        $promotion2->setActive(true);
        $promotion2->setRemoved(false);
        $promotion2->setType('order');
        $promotion2->setSourcePromotionId(200);
        $promotion2->setPromotionName('Order Level Discount');
        $promotion2->setConfigOptions(['type' => 'fixed']);
        $promotion2->setPromotionData(['amount' => 20]);
        $promotion2->addAppliedDiscount($discount3);

        // Create third promotion with coupon but no discounts
        $coupon2 = new AppliedCoupon();
        $coupon2->setCouponCode('FREESHIP');
        $coupon2->setSourcePromotionId(300);
        $coupon2->setSourceCouponId(600);

        $promotion3 = new AppliedPromotion();
        $promotion3->setActive(false);
        $promotion3->setRemoved(true);
        $promotion3->setType('shipping');
        $promotion3->setSourcePromotionId(300);
        $promotion3->setPromotionName('Free Shipping');
        $promotion3->setConfigOptions(['shipping' => 'free']);
        $promotion3->setPromotionData(['method' => 'all']);
        $promotion3->setAppliedCoupon($coupon2);

        // Setup source order
        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->addLineItem($sourceLineItem1);
        $order->addLineItem($sourceLineItem2);
        $order->addAppliedPromotion($promotion1);
        $order->addAppliedPromotion($promotion2);
        $order->addAppliedPromotion($promotion3);

        // Setup duplicated order
        $duplicatedOrder = new Order();
        ReflectionUtil::setId($duplicatedOrder, 200);
        $duplicatedOrder->addLineItem($duplicatedLineItem1);
        $duplicatedOrder->addLineItem($duplicatedLineItem2);

        $event = new OrderDuplicateAfterEvent($order, $duplicatedOrder);

        $this->listener->onOrderDuplicateAfter($event);

        // Verify promotions
        self::assertCount(3, $duplicatedOrder->getAppliedPromotions());

        $clonedPromotions = $duplicatedOrder->getAppliedPromotions()->toArray();

        // Verify first promotion (with coupon and line item discounts)
        $clonedPromotion1 = $clonedPromotions[0];
        self::assertNotSame($promotion1, $clonedPromotion1);
        self::assertTrue($clonedPromotion1->isActive());
        self::assertEquals('line_item', $clonedPromotion1->getType());
        self::assertEquals(100, $clonedPromotion1->getSourcePromotionId());
        self::assertEquals('Item Discount with Coupon', $clonedPromotion1->getPromotionName());
        self::assertCount(2, $clonedPromotion1->getAppliedDiscounts());
        self::assertNotNull($clonedPromotion1->getAppliedCoupon());
        self::assertEquals('SAVE10', $clonedPromotion1->getAppliedCoupon()->getCouponCode());

        $clonedDiscount1 = $clonedPromotion1->getAppliedDiscounts()->toArray()[0];
        self::assertEquals(10.00, $clonedDiscount1->getAmount());
        self::assertSame($duplicatedLineItem1, $clonedDiscount1->getLineItem());

        $clonedDiscount2 = $clonedPromotion1->getAppliedDiscounts()->toArray()[1];
        self::assertEquals(5.00, $clonedDiscount2->getAmount());
        self::assertSame($duplicatedLineItem2, $clonedDiscount2->getLineItem());

        // Verify second promotion (order-level discount, no coupon)
        $clonedPromotion2 = $clonedPromotions[1];
        self::assertNotSame($promotion2, $clonedPromotion2);
        self::assertEquals('order', $clonedPromotion2->getType());
        self::assertEquals(200, $clonedPromotion2->getSourcePromotionId());
        self::assertCount(1, $clonedPromotion2->getAppliedDiscounts());
        self::assertNull($clonedPromotion2->getAppliedCoupon());

        $clonedDiscount3 = $clonedPromotion2->getAppliedDiscounts()->first();
        self::assertEquals(20.00, $clonedDiscount3->getAmount());
        self::assertEquals('EUR', $clonedDiscount3->getCurrency());
        self::assertNull($clonedDiscount3->getLineItem());

        // Verify third promotion (with coupon, no discounts, removed)
        $clonedPromotion3 = $clonedPromotions[2];
        self::assertNotSame($promotion3, $clonedPromotion3);
        self::assertFalse($clonedPromotion3->isActive());
        self::assertTrue($clonedPromotion3->isRemoved());
        self::assertEquals('shipping', $clonedPromotion3->getType());
        self::assertEquals(300, $clonedPromotion3->getSourcePromotionId());
        self::assertCount(0, $clonedPromotion3->getAppliedDiscounts());
        self::assertNotNull($clonedPromotion3->getAppliedCoupon());
        self::assertEquals('FREESHIP', $clonedPromotion3->getAppliedCoupon()->getCouponCode());

        // Verify applied coupons collection
        self::assertCount(2, $duplicatedOrder->getAppliedCoupons());

        $appliedCoupons = $duplicatedOrder->getAppliedCoupons()->toArray();
        self::assertEquals('SAVE10', $appliedCoupons[0]->getCouponCode());
        self::assertEquals('FREESHIP', $appliedCoupons[1]->getCouponCode());

        // Verify all cloned entities reference the duplicated order
        foreach ($clonedPromotions as $clonedPromotion) {
            self::assertSame($duplicatedOrder, $clonedPromotion->getOrder());

            if ($clonedPromotion->getAppliedCoupon()) {
                self::assertSame($duplicatedOrder, $clonedPromotion->getAppliedCoupon()->getOrder());
            }
        }
    }
}
