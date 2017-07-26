<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class DiscountProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @var AppliedDiscountsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appliedDiscountsProvider;

    /**
     * @var PromotionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $executor;

    public function setUp()
    {
        $this->appliedDiscountsProvider = $this->createMock(AppliedDiscountsProvider::class);
        $this->executor = $this->createMock(PromotionExecutor::class);

        $this->discountsProvider = new DiscountsProvider(
            $this->appliedDiscountsProvider,
            $this->executor
        );
    }

    public function testGetDiscountsAmountByOrder()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);

        $context = $this->createMock(DiscountContext::class);
        $this->executor->expects($this->once())->method('execute')->with($order)->willReturn($context);
        $context->expects($this->once())->method('getTotalDiscountAmount')->willReturn(11);

        $this->appliedDiscountsProvider->expects($this->once())->method('getDiscountsAmountByOrder')->willReturn(22);

        $this->discountsProvider->enableRecalculation();
        $this->assertEquals(11, $this->discountsProvider->getDiscountsAmountByOrder($order));
        $this->discountsProvider->disableRecalculation();
        $this->assertEquals(22, $this->discountsProvider->getDiscountsAmountByOrder($order));
    }

    public function testGetDiscountsAmountByLineItem()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => 123]);
        $order->addLineItem($lineItem);
        $lineItem->setOrder($order);

        $context= $this->createMock(DiscountContext::class);
        $this->executor->expects($this->once())->method('execute')->with($order)->willReturn($context);
        $context->expects($this->once())->method('getDiscountByLineItem')->willReturn(11);

        $this->appliedDiscountsProvider->expects($this->once())->method('getDiscountsAmountByLineItem')->willReturn(22);

        $this->discountsProvider->enableRecalculation();
        $this->assertEquals(11, $this->discountsProvider->getDiscountsAmountByLineItem($lineItem));
        $this->discountsProvider->disableRecalculation();
        $this->assertEquals(22, $this->discountsProvider->getDiscountsAmountByLineItem($lineItem));
    }
}
