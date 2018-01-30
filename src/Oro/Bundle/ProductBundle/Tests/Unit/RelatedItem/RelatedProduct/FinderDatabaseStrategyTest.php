<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\AbstractFinderDatabaseStrategyTest;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;

class FinderDatabaseStrategyTest extends AbstractFinderDatabaseStrategyTest
{
    public function testFindProductsIfTheyExist()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), $this->anything(), $expectedResult);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindNoRelatedProductsIfFunctionalityIsDisabled()
    {
        $productA = $this->getProduct(['id' => 1]);

        $this->doctrineHelperShouldNotBeAskedForRepository();
        $this->relatedItemsFunctionalityShouldBeDisabled();

        $this->assertCount(0, $this->strategy->find($productA));
    }

    public function testFindProductsWithLimit()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), 2, $expectedResult);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA, false, 2)
        );
    }

    public function testFindProductsBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, true, $this->anything(), $expectedResult);

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA, true)
        );
    }

    public function testFindProductsNonBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, false, $this->anything(), $expectedResult);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindShouldIgnoredConfigManagerIfArgumentsArePassed()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, false, null, $expectedResult);

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA, false, null)
        );
    }

    /**
     * @param Product $product
     * @param bool|\PHPUnit_Framework_Constraint_IsAnything $bidirectional
     * @param null|int|\PHPUnit_Framework_Constraint_IsAnything $limit
     * @param array $related
     */
    protected function andProductRepositoryShouldFindRelated(
        Product $product,
        $bidirectional,
        $limit,
        array $related
    ) {
        $this->repository
            ->expects($this->once())
            ->method('findRelated')
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
