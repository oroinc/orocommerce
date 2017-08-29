<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;

class AppliedDiscountsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var Cache|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var AppliedDiscountRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var AppliedDiscountsProvider */
    protected $provider;

    public function setUp()
    {
        $this->cache = $this->createMock(Cache::class);
        $this->repository = $this->createMock(AppliedPromotionRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($this->repository);

        $this->provider = new AppliedDiscountsProvider($this->cache, $doctrineHelper);
    }

    public function testGetOrderDiscountAmountFromCache()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);

        $appliedPromotions = [
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(1.1)),
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(2.2)),
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(2.2))
                ->setType(ShippingDiscount::NAME),
        ];

        $this->cache->expects($this->once())->method('contains')->willReturn(true);
        $this->cache->expects($this->once())->method('fetch')->willReturn($appliedPromotions);

        $this->assertSame(3.3, $this->provider->getDiscountsAmountByOrder($order));
    }

    public function testGetShippingDiscountsAmountByOrder()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);

        $expectedAmount = 4.4;
        $promotions = [
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(1.1)),
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(2.2)),
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(2.2))
                ->setType(ShippingDiscount::NAME),
            (new AppliedPromotion)->addAppliedDiscount((new AppliedDiscount())->setAmount(2.2))
                ->setType(ShippingDiscount::NAME),
        ];

        $this->cache->expects($this->once())->method('contains')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findByOrder')
            ->with($order)
            ->willReturn($promotions);

        $this->assertSame($expectedAmount, $this->provider->getShippingDiscountsAmountByOrder($order));
    }

    public function testGetAppliedDiscountsForLineItem()
    {
        /** @var Order $order */
        $order = $this->getEntity(Order::class, ['id' => 123]);

        /** @var OrderLineItem $orderLineItem1 */
        $orderLineItem1 = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $order->addLineItem($orderLineItem1);
        $orderLineItem1->setOrder($order);

        /** @var OrderLineItem $orderLineItem2 */
        $orderLineItem2 = $this->getEntity(OrderLineItem::class, ['id' => 2]);
        $order->addLineItem($orderLineItem2);
        $orderLineItem2->setOrder($order);


        $appliedDiscount1 = new AppliedDiscount();
        $appliedDiscount1->setLineItem($orderLineItem1);
        $appliedDiscount1->setAmount(3.4);

        $appliedDiscount2 = new AppliedDiscount();
        $appliedDiscount2->setLineItem($orderLineItem1);
        $appliedDiscount2->setAmount(1.4);

        $appliedDiscount3 = new AppliedDiscount();
        $appliedDiscount3->setLineItem($orderLineItem2);
        $appliedDiscount3->setAmount(1000.00);

        $this->cache->expects($this->once())->method('contains')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findByOrder')
            ->with($order)
            ->willReturn([
                (new AppliedPromotion())->addAppliedDiscount($appliedDiscount1),
                (new AppliedPromotion())->addAppliedDiscount($appliedDiscount2),
                (new AppliedPromotion())->addAppliedDiscount($appliedDiscount3),
            ]);

        $this->assertEquals(4.8, $this->provider->getDiscountsAmountByLineItem($orderLineItem1));
    }
}
