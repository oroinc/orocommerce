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
    private PromotionRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadCouponData::class,
            ]
        );
        $this->repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class);
    }

    public function testFindPromotionByProductSegment(): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT);
        /** @var Promotion $expectedPromotion */
        $expectedPromotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $actual = $this->repository->findPromotionByProductSegment($segment);
        self::assertInstanceOf(Promotion::class, $actual);
        self::assertSame($expectedPromotion->getId(), $actual->getId());
    }

    public function testGetPromotionsWithLabelsByIds(): void
    {
        /** @var Promotion $expectedPromotion */
        $expectedPromotion = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $actual = $this->repository->getPromotionsWithLabelsByIds([$expectedPromotion->getId(), 0]);
        self::assertSame([$expectedPromotion->getId() => $expectedPromotion], $actual);
    }

    public function testGetPromotionsNamesByIds(): void
    {
        /** @var Promotion $expectedPromotion */
        $promotion1 = $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION);
        $promotion2 = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);

        $actual = $this->repository->getPromotionsNamesByIds([$promotion1->getId(), $promotion2->getId(), 0, '', null]);
        self::assertEquals(
            [
                $promotion1->getId() => $promotion1->getRule()->getName(),
                $promotion2->getId() => $promotion2->getRule()->getName(),
            ],
            $actual
        );
    }

    public function testGetPromotionsNamesWhenNoPromotions(): void
    {
        $actual = $this->repository->getPromotionsNamesByIds([PHP_INT_MAX]);
        self::assertEquals([], $actual);
    }

    public function testGetPromotionsNamesWhenNoIds(): void
    {
        $actual = $this->repository->getPromotionsNamesByIds([]);
        self::assertEquals([], $actual);
    }
}
