<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedDiscountData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AppliedDiscountRepositoryTest extends WebTestCase
{
    /**
     * @var AppliedDiscountRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadAppliedDiscountData::class,
                LoadOrderLineItems::class,
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AppliedDiscount::class)
            ->getRepository(AppliedDiscount::class);
    }

    public function testIsRepositoryConnectedToEntity()
    {
        $this->assertInstanceOf(AppliedDiscountRepository::class, $this->repository);
    }

    public function testFindPromotionByOrder()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedDiscounts = $this->repository->findByOrder($order);
        $this->assertNotEmpty($appliedDiscounts);
    }

    public function testFindPromotionByOrderLineItem()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItems::ITEM_1);

        $appliedDiscounts = $this->repository->findByLineItem($lineItem);
        $this->assertNotEmpty($appliedDiscounts);
    }
}
