<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PromotionRepositoryTest extends WebTestCase
{
    private PromotionRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCouponData::class]);
        $this->repository = self::getContainer()->get('doctrine')->getRepository(Promotion::class);
    }

    private function getPromotion(string $reference): Promotion
    {
        return $this->getReference($reference);
    }

    private function getPromotionIds(array $promotions): array
    {
        return array_map(fn (Promotion $promotion) => $promotion->getId(), $promotions);
    }

    private function getScopeManager(): ScopeManager
    {
        return self::getContainer()->get('oro_scope.scope_manager');
    }

    private function getCriteria(): ScopeCriteria
    {
        return $this->getScopeManager()->getCriteria('promotion');
    }

    public function testGetAvailablePromotions(): void
    {
        $promotions = $this->repository->getAvailablePromotions($this->getCriteria(), 'USD');
        self::assertEquals(
            [
                $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId(),
                $this->getPromotion(LoadPromotionData::ORDER_AMOUNT_PROMOTION)->getId(),
                $this->getPromotion(LoadPromotionData::SHIPPING_PROMOTION)->getId()
            ],
            $this->getPromotionIds($promotions)
        );
    }

    public function testGetAvailablePromotionsForAnotherCurrency(): void
    {
        $promotions = $this->repository->getAvailablePromotions($this->getCriteria(), 'EUR');
        self::assertEquals(
            [
                $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId()
            ],
            $this->getPromotionIds($promotions)
        );
    }

    public function testGetAvailablePromotionsWithNullCurrency(): void
    {
        $promotions = $this->repository->getAvailablePromotions($this->getCriteria(), null);
        self::assertEquals(
            [
                $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId()
            ],
            $this->getPromotionIds($promotions)
        );
    }

    public function testFindPromotionByProductSegment(): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT);
        $expectedPromotion = $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION);

        $actual = $this->repository->findPromotionByProductSegment($segment);
        self::assertInstanceOf(Promotion::class, $actual);
        self::assertSame($expectedPromotion->getId(), $actual->getId());
    }

    public function testGetPromotionsNamesByIds(): void
    {
        $promotion1 = $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION);
        $promotion2 = $this->getPromotion(LoadPromotionData::SHIPPING_PROMOTION);

        $actual = $this->repository->getPromotionsNamesByIds([$promotion1->getId(), $promotion2->getId(), 0, '', null]);
        self::assertEquals(
            [
                $promotion1->getId() => $promotion1->getRule()->getName(),
                $promotion2->getId() => $promotion2->getRule()->getName(),
            ],
            $actual
        );
    }

    public function testGetPromotionsNamesByIdsWhenNoPromotions(): void
    {
        $actual = $this->repository->getPromotionsNamesByIds([PHP_INT_MAX]);
        self::assertEquals([], $actual);
    }

    public function testGetPromotionsNamesByIdsWhenNoIds(): void
    {
        $actual = $this->repository->getPromotionsNamesByIds([]);
        self::assertEquals([], $actual);
    }
}
