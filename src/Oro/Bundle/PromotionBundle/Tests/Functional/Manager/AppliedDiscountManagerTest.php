<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedDiscountData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;

class AppliedDiscountManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadAppliedDiscountData::class,
            LoadPromotionData::class,
        ]);
    }

    public function testRemoveAppliedDiscountByOrderLineItem()
    {
        $appliedDiscountManager = $this->getAppliedDiscountManager();

        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getReference(LoadOrderLineItems::ITEM_1);

        $appliedDiscounts = $this->getAppliedDiscountRepository()->findByLineItem($orderLineItem);
        $this->assertNotEmpty($appliedDiscounts);

        $appliedDiscountManager->removeAppliedDiscountByOrderLineItem($orderLineItem, true);

        $appliedDiscountsAfterRemove = $this->getAppliedDiscountRepository()->findByLineItem($orderLineItem);
        $this->assertEmpty($appliedDiscountsAfterRemove);
    }

    public function testRemoveAppliedDiscountByOrder()
    {
        $appliedDiscountManager = $this->getAppliedDiscountManager();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedDiscounts = $this->getAppliedDiscountRepository()->findByOrder($order);
        $this->assertNotEmpty($appliedDiscounts);

        $appliedDiscountManager->removeAppliedDiscountByOrder($order, true);

        $appliedDiscountsAfterRemove = $this->getAppliedDiscountRepository()->findByOrder($order);
        $this->assertEmpty($appliedDiscountsAfterRemove);
    }

    public function testSaveAppliedDiscounts()
    {
        $appliedDiscountManager = $this->getAppliedDiscountManager();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedDiscounts = $this->getAppliedDiscountRepository()->findByOrder($order);
        $this->assertEmpty($appliedDiscounts);

        $appliedDiscountManager->saveAppliedDiscounts($order, true);

        $appliedDiscountsAfterSave = $this->getAppliedDiscountRepository()->findByOrder($order);
        $this->assertCount(2, $appliedDiscountsAfterSave);
    }

    /**
     * @return AppliedDiscountManager
     */
    protected function getAppliedDiscountManager()
    {
        return $this->getContainer()->get('oro_promotion.applied_discount_manager');
    }

    /**
     * @return AppliedDiscountRepository|\Doctrine\ORM\EntityRepository
     */
    protected function getAppliedDiscountRepository()
    {
        return $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AppliedDiscount::class);
    }
}
