<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Provider\ProductsUsageStatsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductsUsageStatsProviderTest extends TestCase
{
    private ProductRepository|MockObject $productRepository;
    private OrganizationRestrictionProviderInterface|MockObject $organizationRestrictionProvider;
    private ProductsUsageStatsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);

        $this->provider = new ProductsUsageStatsProvider(
            $this->productRepository,
            $this->organizationRestrictionProvider
        );
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->provider->isApplicable());
    }

    public function testGetTitle(): void
    {
        self::assertEquals(
            'oro.product.usage_stats.products.label',
            $this->provider->getTitle()
        );
    }

    public function testGetTooltip(): void
    {
        self::assertNull($this->provider->getTooltip());
    }

    public function testGetValue(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->productRepository->expects(self::once())
            ->method('getProductCountQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(15);

        $this->organizationRestrictionProvider->expects(self::once())
            ->method('applyOrganizationRestrictions')
            ->with($queryBuilder);

        self::assertSame(
            '15',
            $this->provider->getValue()
        );
    }
}
