<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;

class CustomerProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    #[\Override]
    public function buildCacheDataProvider(): array
    {
        return [
            [
                'expectedStaticCount' => 5,
                'expectedCategoryCount' => 1,
            ],
        ];
    }

    #[\Override]
    protected function getRepository(): CustomerProductRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerProductVisibilityResolved::class);
    }

    #[\Override]
    protected function getCacheBuilder(): CacheBuilderInterface
    {
        return self::getContainer()->get(
            'oro_visibility.visibility.cache.product.customer_product_resolved_cache_builder'
        );
    }
}
