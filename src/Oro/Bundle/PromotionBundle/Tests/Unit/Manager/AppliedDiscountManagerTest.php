<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\OrderDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class AppliedDiscountManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AppliedDiscountManager */
    protected $appliedDiscountManager;

    /** @var PromotionExecutor|\PHPUnit_Framework_MockObject_MockObject */
    protected $promotionExecutor;

    protected function setUp()
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->with('oro_promotion.promotion_executor')
            ->willReturn($this->promotionExecutor);

        $this->appliedDiscountManager = new AppliedDiscountManager($container);
    }

    public function testGetAppliedDiscounts()
    {
        $discountConfiguration = (new DiscountConfiguration())
            ->setOptions([1, 2, 3])
            ->setType('test-type');
        $promotion = new Promotion();
        $promotion->setRule((new Rule())->setName('first promotion'));
        $promotion->setDiscountConfiguration($discountConfiguration);

        $order = new Order();
        $order->setCurrency('USD');

        $orderDiscount = new OrderDiscount(new ShippingDiscount());
        $orderDiscount->setPromotion($promotion);

        $discountContext = new DiscountContext();
        $discountContext->addSubtotalDiscountInformation(
            new DiscountInformation($orderDiscount, 12.34)
        );

        $lineItemDiscount = new LineItemsDiscount(new ShippingDiscount());
        $lineItemDiscount->setPromotion($promotion);

        $orderLineItem = new OrderLineItem();
        $discountLineItem = new DiscountLineItem();
        $discountLineItem->addDiscountInformation(
            new DiscountInformation($lineItemDiscount, 56.78)
        );
        $discountLineItem->setSourceLineItem($orderLineItem);
        $discountContext->addLineItem($discountLineItem);

        $this->promotionExecutor->expects($this->once())->method('execute')->with($order)->willReturn($discountContext);

        $expected = [
            (new AppliedDiscount())
                ->setOrder($order)
                ->setType('test-type')
                ->setAmount(12.34)
                ->setCurrency('USD')
                ->setConfigOptions([1, 2, 3])
                ->setPromotion($promotion)
                ->setPromotionName('first promotion'),
            (new AppliedDiscount())
                ->setOrder($order)
                ->setType('test-type')
                ->setAmount(56.78)
                ->setCurrency('USD')
                ->setConfigOptions([1, 2, 3])
                ->setPromotion($promotion)
                ->setPromotionName('first promotion')
                ->setLineItem($orderLineItem),
        ];

        $this->assertEquals($expected, $this->appliedDiscountManager->createAppliedDiscounts($order));
    }
}
