<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;

class OrdersAppliedDiscountsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Cache|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var OrdersAppliedDiscountsProvider */
    protected $provider;

    public function setUp()
    {
        $this->cache = $this->createMock(Cache::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(AppliedDiscount::class)
            ->willReturn($this->repository);

        $this->provider = new OrdersAppliedDiscountsProvider($this->cache, $doctrineHelper);
    }

    public function testGetOrderDiscountsFromCache()
    {
        $discounts = [new AppliedDiscount(), new AppliedDiscount()];

        $this->cache->expects($this->once())->method('contains')->willReturn(true);
        $this->cache->expects($this->once())->method('fetch')->willReturn($discounts);

        $this->assertSame($discounts, $this->provider->getOrderDiscounts(123));
    }

    public function testGetGetOrdersDiscounts()
    {
        $orderId = 123;
        $discounts = [new AppliedDiscount(), new AppliedDiscount()];

        $this->cache->expects($this->once())->method('contains')->willReturn(false);
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['order' => $orderId])
            ->willReturn($discounts);
        $this->cache->expects($this->once())->method('save');

        $this->assertSame($discounts, $this->provider->getOrderDiscounts($orderId));
    }

    public function testGetOrderDiscountAmount()
    {
        $orderId = 123;

        $expectedAmount = 3.3;
        $discounts = [
            (new AppliedDiscount())->setAmount(1.1),
            (new AppliedDiscount())->setAmount(2.2),
        ];

        $this->cache->expects($this->once())->method('contains')->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['order' => $orderId])
            ->willReturn($discounts);

        $this->assertSame($expectedAmount, $this->provider->getOrderDiscountAmount($orderId));
    }
}
