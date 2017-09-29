<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Mapper\OrderMapperDecorator;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderMapperDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var MapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMapper;

    /**
     * @var OrderMapperDecorator
     */
    private $orderMapperDecorator;

    protected function setUp()
    {
        $this->orderMapper = $this->createMock(MapperInterface::class);
        $this->orderMapperDecorator = new OrderMapperDecorator($this->orderMapper);
    }

    public function testMapWhenSourceEntityIsNotShoppingList()
    {
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $checkout = (new Checkout())->setSource($checkoutSource);
        $order = $this->createMock(Order::class);
        $order->expects($this->never())
            ->method('addAppliedCoupon');
        $data = ['paymentTerm' => 'Term30'];

        $this->orderMapper
            ->expects($this->once())
            ->method('map')
            ->with($checkout, $data)
            ->willReturn($order);

        static::assertSame($order, $this->orderMapperDecorator->map($checkout, $data));
    }

    public function testMapWhenSourceEntityIsShoppingList()
    {
        $couponCode = 'First';
        $sourcePromotionId = 3;
        $sourceCouponId = 22;
        $appliedPromotion = new AppliedPromotion();

        $appliedCoupon = $this->getEntity(AppliedCoupon::class, [
            'id' => 5,
            'couponCode' => $couponCode,
            'sourcePromotionId' => $sourcePromotionId,
            'sourceCouponId' => $sourceCouponId,
            'appliedPromotion' => $appliedPromotion
        ]);

        $shoppingList = new ShoppingList();
        $shoppingList->setAppliedCoupons([$appliedCoupon]);

        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource
            ->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $checkout = (new Checkout())->setSource($checkoutSource);

        $order = new Order();
        $data = ['paymentTerm' => 'Term30'];

        $this->orderMapper
            ->expects($this->once())
            ->method('map')
            ->with($checkout, $data)
            ->willReturn($order);

        $expectedAppliedCoupon = (new AppliedCoupon())
            ->setCouponCode($couponCode)
            ->setSourcePromotionId($sourcePromotionId)
            ->setSourceCouponId($sourceCouponId)
            ->setAppliedPromotion($appliedPromotion);

        $expectedOrder = new Order();
        $expectedOrder->addAppliedCoupon($expectedAppliedCoupon);

        static::assertEquals($expectedOrder, $this->orderMapperDecorator->map($checkout, $data));
    }
}
