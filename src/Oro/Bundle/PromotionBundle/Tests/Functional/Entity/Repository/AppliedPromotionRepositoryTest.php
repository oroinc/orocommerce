<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AppliedPromotionRepositoryTest extends WebTestCase
{
    /**
     * @var AppliedPromotionRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadAppliedPromotionData::class]);

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AppliedPromotion::class)
            ->getRepository(AppliedPromotion::class);
    }

    public function testFindByOrder()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $expected = [
            $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_DISCOUNT)->getId(),
            $this->getReference(LoadAppliedPromotionData::SIMPLE_APPLIED_DISCOUNT_WITH_LINE_ITEM)->getId()
        ];

        $actualAppliedPromotion = $this->repository->findByOrder($order);

        $this->assertCount(count($expected), $actualAppliedPromotion);

        foreach ($actualAppliedPromotion as $appliedPromotion) {
            $this->assertContains($appliedPromotion->getId(), $expected);
        }
    }
}
