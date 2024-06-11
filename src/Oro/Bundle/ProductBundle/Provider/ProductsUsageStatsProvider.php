<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

/**
 * Usage Stats provider for the number of products in the system
 */
class ProductsUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ProductRepository $productRepository;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;

    public function __construct(
        ProductRepository $productRepository,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider
    ) {
        $this->productRepository = $productRepository;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
    }

    public function getTitle(): string
    {
        return 'oro.product.usage_stats.products.label';
    }

    public function getValue(): ?string
    {
        $queryBuilder = $this->productRepository->getProductCountQueryBuilder();

        $this->organizationRestrictionProvider->applyOrganizationRestrictions(
            $queryBuilder
        );

        return (string)$queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}
