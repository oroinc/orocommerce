<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedProducts;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\Strategy\DatabaseStrategy;

use Oro\Component\Testing\Unit\EntityTrait;

class DatabaseStrategyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DatabaseStrategy
     */
    private $strategy;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var RelatedProductsConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    protected function setUp()
    {
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder(RelatedProductsConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new DatabaseStrategy(
            $this->doctrineHelper,
            $this->configProvider
        );
    }

    public function testGetRelatedProductsIfTheyExist()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->doctrineHelperShouldReturnProductRepository();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), $this->anything(), $expectedResult);

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andShouldHaveLimit(3);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findRelatedProducts($productA)
        );
    }

    public function testGetNoRelatedProductsIfFunctionalityIsDisabled()
    {
        $productA = $this->getProduct(['id' => 1]);

        $this->doctrineHelperShouldNotBeAskedForRepository();
        $this->relatedProductFunctionalityShouldBeDisabled();

        $this->assertCount(0, $this->strategy->findRelatedProducts($productA));
    }

    public function testGetRelatedProductsWithLimit()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult =[$productB, $productC];

        $this->doctrineHelperShouldReturnProductRepository();
        $this->andProductRepositoryShouldFindRelated($productA, $this->anything(), 2, $expectedResult);

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andShouldHaveLimit(2);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findRelatedProducts($productA)
        );
    }

    public function testGetRelatedProductsBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->doctrineHelperShouldReturnProductRepository();
        $this->andProductRepositoryShouldFindRelated($productA, true, $this->anything(), $expectedResult);

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andShouldHaveLimit(2);
        $this->andShouldBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findRelatedProducts($productA)
        );
    }

    public function testGetRelatedProductsNonBidirectional()
    {
        $productA = $this->getProduct(['id' => 1]);
        $productB = $this->getProduct(['id' => 2]);
        $productC = $this->getProduct(['id' => 3]);
        $expectedResult = [$productB, $productC];

        $this->doctrineHelperShouldReturnProductRepository();
        $this->andProductRepositoryShouldFindRelated($productA, false, $this->anything(), $expectedResult);

        $this->relatedProductFunctionalityShouldBeEnabled();
        $this->andShouldHaveLimit(2);
        $this->andShouldNotBeBidirectional();

        $this->assertSame(
            $expectedResult,
            $this->strategy->findRelatedProducts($productA)
        );
    }

    private function doctrineHelperShouldReturnProductRepository()
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);
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
     * @param int|\PHPUnit_Framework_Constraint_IsAnything $limit
     * @param array $related
     */
    private function andProductRepositoryShouldFindRelated(
        Product $product,
        $bidirectional,
        $limit,
        array $related
    ) {
        $this->productRepository
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
    private function getProduct($properties = [])
    {
        return $this->getEntity(Product::class, $properties);
    }
}
