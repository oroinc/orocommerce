<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\RelatedProduct;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Component\Testing\ReflectionUtil;

class FinderDatabaseStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var RelatedItemConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FinderDatabaseStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RelatedProductRepository::class);
        $this->configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(RelatedProduct::class)
            ->willReturn($this->repository);

        $this->strategy = new FinderDatabaseStrategy($doctrine, $this->configProvider);
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    public function testFindIdsWhenRelatedProductsDisabled(): void
    {
        $product = $this->getProduct(1);

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->repository->expects(self::never())
            ->method('findRelatedIds');

        self::assertSame([], $this->strategy->findIds($product));
    }

    public function testFindIdsWhenRelatedProductsEnabled(): void
    {
        $product = $this->getProduct(1);
        $foundProductIds = [2, 3];

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->repository->expects(self::once())
            ->method('findRelatedIds')
            ->with($product->getId(), false, null)
            ->willReturn($foundProductIds);

        self::assertSame($foundProductIds, $this->strategy->findIds($product));
    }

    public function testFindIdsWhenRelatedProductsEnabledAndWithCustomParameters(): void
    {
        $product = $this->getProduct(1);
        $foundProductIds = [2, 3];
        $bidirectional = true;
        $limit = 10;

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->repository->expects(self::once())
            ->method('findRelatedIds')
            ->with($product->getId(), $bidirectional, $limit)
            ->willReturn($foundProductIds);

        self::assertSame($foundProductIds, $this->strategy->findIds($product, $bidirectional, $limit));
    }
}
