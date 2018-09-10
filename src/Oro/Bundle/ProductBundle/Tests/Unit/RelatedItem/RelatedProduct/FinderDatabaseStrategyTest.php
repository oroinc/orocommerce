<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\AbstractFinderDatabaseStrategyTest;

class FinderDatabaseStrategyTest extends AbstractFinderDatabaseStrategyTest
{
    public function testFindIdsIfTheyExist()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB->getId(), $productC->getId()];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelatedIds($productA, false, null, $expectedResult);
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findIds($productA)
        );
    }

    public function testFindIdsNoRelatedProductsIfFunctionalityIsDisabled()
    {
        $productA = $this->getProduct(['id' => 1]);

        $this->doctrineHelperShouldNotBeAskedForRepository();
        $this->relatedItemsFunctionalityShouldBeDisabled();
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertCount(0, $this->strategy->findIds($productA));
    }

    public function testFindIdsWithLimit()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB->getId(), $productC->getId()];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelatedIds($productA, false, 2, $expectedResult);
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findIds($productA, false, 2)
        );
    }

    public function testFindIdsBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelatedIds($productA, true, null, $expectedResult);
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findIds($productA, true)
        );
    }

    public function testFindIdsNonBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelatedIds($productA, false, null, $expectedResult);
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findIds($productA)
        );
    }

    public function testFindIdsShouldIgnoredConfigManagerIfArgumentsArePassed()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelatedIds($productA, false, null, $expectedResult);
        $this->configManagerBidirectionalOptionShouldBeIgnored();
        $this->configManagerLimitOptionShouldBeIgnored();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findIds($productA, false, null)
        );
    }

    /**
     * @param Product $product
     * @param bool|\PHPUnit\Framework\Constraint\IsAnything $bidirectional
     * @param null|int|\PHPUnit\Framework\Constraint\IsAnything $limit
     * @param array $related
     */
    protected function andProductRepositoryShouldFindRelatedIds(
        Product $product,
        $bidirectional,
        $limit,
        array $related
    ) {
        $this->repository
            ->expects($this->once())
            ->method('findRelatedIds')
            ->with($product->getId(), $bidirectional, $limit)
            ->willReturn($related);
    }

    /**
     * @return FinderDatabaseStrategy
     */
    public function createFinderStrategy()
    {
        return new FinderDatabaseStrategy(
            $this->doctrineHelper,
            $this->configProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createRepositoryMock()
    {
        return $this->createMock(RelatedProductRepository::class);
    }
}
