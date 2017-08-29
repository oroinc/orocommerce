<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;

class AppliedDiscountManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadAppliedPromotionData::class,
            LoadPromotionData::class,
        ]);
    }

    public function testRemoveAppliedPromotionsByOrder()
    {
        $appliedDiscountManager = $this->getAppliedDiscountManager();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedDiscounts = $this->getAppliedPromotionRepository()->findByOrder($order);
        $this->assertNotEmpty($appliedDiscounts);

        $appliedDiscountManager->removeAppliedDiscountByOrder($order, true);

        $appliedDiscountsAfterRemove = $this->getAppliedPromotionRepository()->findByOrder($order);
        $this->assertEmpty($appliedDiscountsAfterRemove);
    }

    public function testSaveAppliedDiscounts()
    {
        $appliedDiscountManager = $this->getAppliedDiscountManager();

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedDiscounts = $this->getAppliedPromotionRepository()->findByOrder($order);
        $this->assertEmpty($appliedDiscounts);

        $appliedDiscountManager->saveAppliedDiscounts($order, true);

        $appliedDiscountsAfterSave = $this->getAppliedPromotionRepository()->findByOrder($order);
        $this->assertCount(1, $appliedDiscountsAfterSave);
    }

    /**
     * @return AppliedDiscountManager
     */
    protected function getAppliedDiscountManager()
    {
        return static::getContainer()->get('oro_promotion.applied_discount_manager');
    }

    /**
     * @return AppliedPromotionRepository|\Doctrine\ORM\EntityRepository
     */
    protected function getAppliedPromotionRepository()
    {
        return static::getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(AppliedPromotion::class);
    }
}
