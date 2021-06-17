<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\OrderTax\Specification;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\OrderTax\Specification\OrderWithChangedPromotionsCollectionSpecification;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class OrderWithChangedPromotionsCollectionSpecificationTest extends WebTestCase
{
    private OrderWithChangedPromotionsCollectionSpecification $specification;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadAppliedPromotionData::class]);

        $uow = $this->getContainer()->get('doctrine')->getManager()->getUnitOfWork();

        $this->specification = new OrderWithChangedPromotionsCollectionSpecification($uow);
    }

    public function testNotOrderWillNotRequireTaxRecalculation(): void
    {
        self::assertFalse($this->specification->isSatisfiedBy(new \stdClass()));
    }

    public function testOrderWithoutChangesWillNotRequireTaxRecalculation(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);

        self::assertFalse($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedPromotionsCollectionWillRequireTaxRecalculation(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedPromotion = new AppliedPromotion();
        $order->addAppliedPromotion($appliedPromotion);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }

    public function testOrderWithChangedPromotionStatusWillRequireTaxRecalculation(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedPromotion = $order->getAppliedPromotions()[0];
        $appliedPromotion->setActive(false);

        self::assertTrue($this->specification->isSatisfiedBy($order));
    }
}
