<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;

class ResolvedProductVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilderModifier;

    /** @var ResolvedProductVisibilityProvider */
    private $resolvedProductVisibilityProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->queryBuilderModifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);

        $this->resolvedProductVisibilityProvider = new ResolvedProductVisibilityProvider(
            $this->doctrine,
            $this->queryBuilderModifier
        );
    }

    public function testIsVisibleWhenPrefetched(): void
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManger = $this->createMock(EntityManager::class));

        $entityManger
            ->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository = $this->createMock(ProductRepository::class));

        $repository
            ->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with($productIds = [1, 2, 3])
            ->willReturn($qb = $this->createMock(QueryBuilder::class));

        $this->queryBuilderModifier
            ->expects($this->once())
            ->method('modify')
            ->with($qb)
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('resetDQLPart')
            ->with('select')
            ->willReturnSelf();

        $qb
            ->expects($this->once())
            ->method('select')
            ->with('p.id');

        $qb
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query = $this->createMock(AbstractQuery::class));

        $query
            ->expects($this->once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([['id' => 1], ['id' => 2]]);

        $this->resolvedProductVisibilityProvider->prefetch($productIds);
        // Checks caching.
        $this->resolvedProductVisibilityProvider->prefetch($productIds);

        $this->assertTrue($this->resolvedProductVisibilityProvider->isVisible(1));
        $this->assertTrue($this->resolvedProductVisibilityProvider->isVisible(2));
        $this->assertFalse($this->resolvedProductVisibilityProvider->isVisible(3));
    }

    public function testIsVisibleWhenNotPrefetched(): void
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManger = $this->createMock(EntityManager::class));

        $entityManger
            ->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repository = $this->createMock(ProductRepository::class));

        $repository
            ->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with($productIds = [1])
            ->willReturn($qb = $this->createMock(QueryBuilder::class));

        $this->queryBuilderModifier
            ->expects($this->once())
            ->method('modify')
            ->with($qb)
            ->willReturn($qb);

        $qb
            ->expects($this->once())
            ->method('resetDQLPart')
            ->with('select')
            ->willReturnSelf();

        $qb
            ->expects($this->once())
            ->method('select')
            ->with('p.id');

        $qb
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query = $this->createMock(AbstractQuery::class));

        $query
            ->expects($this->once())
            ->method('getResult')
            ->with(AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([['id' => 1]]);

        $this->assertTrue($this->resolvedProductVisibilityProvider->isVisible(1));
    }
}
