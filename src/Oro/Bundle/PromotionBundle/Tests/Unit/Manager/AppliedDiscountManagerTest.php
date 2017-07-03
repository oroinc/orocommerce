<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class AppliedDiscountManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var AppliedDiscountManager */
    protected $appliedDiscountManager;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->appliedDiscountManager = new AppliedDiscountManager($this->container);
    }

    public function testGetAppliedDiscounts()
    {
        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $order **/
        $order = $this->createMock(Order::class);

        $promotion = (new Promotion())->setDiscountConfiguration((new DiscountConfiguration())->setOptions([]));

        $orderDiscount = $this->createMock(OrderDiscount::class);
        $orderDiscount->expects($this->once())->method('getPromotion')->willReturn($promotion);
        $orderDiscount->expects($this->once())->method('getDiscountType')->willReturn('percent');
        $orderDiscount->expects($this->once())->method('getDiscountValue')->willReturn(10.0);
        $orderDiscount->expects($this->once())->method('getDiscountCurrency')->willReturn('USD');

        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())->method('getSubtotalDiscounts')->willReturn([$orderDiscount]);
        $discountContext->expects($this->once())->method('getShippingDiscounts')->willReturn([]);
        $discountContext->expects($this->once())->method('getLineItems')->willReturn([]);

        $promotionExecutor = $this->createMock(PromotionExecutor::class);
        $promotionExecutor->expects($this->once())->method('execute')->with($order)
            ->willReturn($discountContext);

        $this->container->expects($this->once())->method('get')
            ->with('oro_promotion.promotion_executor')
            ->willReturn($promotionExecutor);

        $appliedDiscount = (new AppliedDiscount())
            ->setOrder($order)
            ->setType('percent')
            ->setAmount(10.0)
            ->setCurrency('USD')
            ->setConfigOptions($promotion->getDiscountConfiguration()->getOptions())
            ->setOptions([])
            ->setPromotion($promotion);

        $this->assertEquals([$appliedDiscount], $this->appliedDiscountManager->createAppliedDiscounts($order));
    }
}
