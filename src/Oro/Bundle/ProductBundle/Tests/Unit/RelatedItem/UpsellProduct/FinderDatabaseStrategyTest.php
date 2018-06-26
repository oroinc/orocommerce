<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\RelatedItem\UpsellProduct;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\FinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\AbstractFinderDatabaseStrategyTest;

class FinderDatabaseStrategyTest extends AbstractFinderDatabaseStrategyTest
{
    public function testFindProductsIfTheyExist()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];
        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindUpsell($productA, $this->anything(), $expectedResult);
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
        $expectedResult =[$productB, $productC];
        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindUpsell($productA, 2, $expectedResult);
        $this->andShouldNotBeBidirectional();
        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA, $this->anything(), 2)
        );
    }

    public function testFindShouldIgnoredConfigManagerForBackend()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];
        $this->relatedItemsFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindUpsell($productA, null, $expectedResult);
        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    /**
     * @param Product $product
     * @param null|int|\PHPUnit\Framework\Constraint\IsAnything $limit
     * @param array $upsell
     */
    protected function andProductRepositoryShouldFindUpsell(Product $product, $limit, array $upsell)
    {
        $this->repository
            ->expects($this->once())
            ->method('findUpsell')
            ->with($product->getId(), $limit)
            ->willReturn($upsell);
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
        return $this->createMock(UpsellProductRepository::class);
    }
}
