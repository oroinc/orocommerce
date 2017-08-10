<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
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
