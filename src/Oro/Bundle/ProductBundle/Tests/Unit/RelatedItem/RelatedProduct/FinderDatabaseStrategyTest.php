<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\RelatedProduct;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Component\Testing\Unit\EntityTrait;

class FinderDatabaseStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FinderDatabaseStrategy
     */
    private $strategy;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $frontendHelper;

    /**
     * @var RelatedProductsConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(RelatedProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(RelatedProduct::class)
            ->willReturn($this->repository);

        $this->configProvider = $this->getMockBuilder(RelatedProductsConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new FinderDatabaseStrategy(
            $this->doctrineHelper,
            $this->configProvider,
            $this->frontendHelper
        );
    }

    public function testFindProductsIfTheyExist()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), $this->anything(), $expectedResult);
        $this->andShouldHaveLimit(3);
        $this->andShouldNotBeBidirectional();
        $this->andRequestCameFromFrontend();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindNoRelatedProductsIfFunctionalityIsDisabled()
    {
        $productA = $this->getProduct(['id' => 1]);

        $this->doctrineHelperShouldNotBeAskedForRepository();
        $this->relatedProductFunctionalityShouldBeDisabled();

        $this->assertCount(0, $this->strategy->find($productA));
    }

    public function testFindProductsWithLimit()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult =[$productB, $productC];

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), 2, $expectedResult);
        $this->andShouldHaveLimit(2);
        $this->andShouldNotBeBidirectional();
        $this->andRequestCameFromFrontend();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindProductsBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, true, $this->anything(), $expectedResult);
        $this->andShouldHaveLimit(2);
        $this->andShouldBeBidirectional();
        $this->andRequestCameFromFrontend();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindProductsNonBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, false, $this->anything(), $expectedResult);
        $this->andShouldHaveLimit(2);
        $this->andShouldNotBeBidirectional();
        $this->andRequestCameFromFrontend();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    public function testFindShouldIgnoredConfigManagerForBackend()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andProductRepositoryShouldFindRelated($productA, false, null, $expectedResult);
        $this->andRequestCameFromBackend();

        $this->assertSame(
            $expectedResult,
            $this->strategy->find($productA)
        );
    }

    private function doctrineHelperShouldNotBeAskedForRepository()
    {
        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityManager')
            ->with(Product::class);
    }

    /**
     * @param Product $product
     * @param bool|\PHPUnit_Framework_Constraint_IsAnything $bidirectional
     * @param null|int|\PHPUnit_Framework_Constraint_IsAnything $limit
     * @param array $related
     */
    private function andProductRepositoryShouldFindRelated(
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

    private function relatedProductFunctionalityShouldBeEnabled()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
    }

    private function relatedProductFunctionalityShouldBeDisabled()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
    }

    private function andShouldHaveLimit($limit)
    {
        $this->configProvider
            ->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);
    }

    private function andShouldBeBidirectional()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isBidirectional')
            ->willReturn(true);
    }

    private function andShouldNotBeBidirectional()
    {
        $this->configProvider
            ->expects($this->any())
            ->method('isBidirectional')
            ->willReturn(false);
    }

    /**
     * @param array $properties
     * @return Product
     */
    private function getProduct(array $properties = [])
    {
        return $this->getEntity(Product::class, $properties);
    }

    private function andRequestCameFromFrontend()
    {
        $this->frontendHelper
            ->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);
    }

    private function andRequestCameFromBackend()
    {
        $this->frontendHelper
            ->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(false);
    }
}
