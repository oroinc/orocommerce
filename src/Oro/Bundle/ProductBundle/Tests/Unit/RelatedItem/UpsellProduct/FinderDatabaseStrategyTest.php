<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedItem\UpsellProduct;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\UpsellProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct\FinderDatabaseStrategy;
use Oro\Component\Testing\ReflectionUtil;

class FinderDatabaseStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpsellProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var RelatedItemConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FinderDatabaseStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UpsellProductRepository::class);
        $this->configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(UpsellProduct::class)
            ->willReturn($this->repository);

        $this->strategy = new FinderDatabaseStrategy($doctrine, $this->configProvider);
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    public function testFindIdsWhenUpsellProductsDisabled(): void
    {
        $product = $this->getProduct(1);

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->repository->expects(self::never())
            ->method('findUpsellIds');

        self::assertSame([], $this->strategy->findIds($product));
    }

    public function testFindIdsWhenUpsellProductsEnabled(): void
    {
        $product = $this->getProduct(1);
        $foundProductIds = [2, 3];

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->repository->expects(self::once())
            ->method('findUpsellIds')
            ->with($product->getId(), null)
            ->willReturn($foundProductIds);

        self::assertSame($foundProductIds, $this->strategy->findIds($product));
    }

    public function testFindIdsWhenUpsellProductsEnabledAndWithCustomParameters(): void
    {
        $product = $this->getProduct(1);
        $foundProductIds = [2, 3];
        $bidirectional = true;
        $limit = 10;

        $this->configProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->repository->expects(self::once())
            ->method('findUpsellIds')
            ->with($product->getId(), $limit)
            ->willReturn($foundProductIds);

        self::assertSame($foundProductIds, $this->strategy->findIds($product, $bidirectional, $limit));
    }
}
