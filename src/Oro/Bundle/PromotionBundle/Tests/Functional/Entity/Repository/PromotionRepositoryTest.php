<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PromotionRepositoryTest extends WebTestCase
{
    /**
     * @var PromotionRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadCouponData::class
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class);
    }

    public function testFindPromotionByProductSegment()
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT);
        /** @var Promotion $expectedPromotion */
        $expectedPromotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $actual = $this->repository->findPromotionByProductSegment($segment);
        $this->assertInstanceOf(Promotion::class, $actual);
        $this->assertSame($expectedPromotion->getId(), $actual->getId());
    }

    public function testGetPromotionsWithLabelsByIds()
    {
        /** @var Promotion $expectedPromotion */
        $expectedPromotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $actual = $this->repository->getPromotionsWithLabelsByIds([$expectedPromotion->getId(), 0]);
        $this->assertSame([$expectedPromotion->getId() => $expectedPromotion], $actual);
    }
}
