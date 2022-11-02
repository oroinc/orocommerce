<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;

class CustomerGroupProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider(): array
    {
        return [
            [
                'expectedStaticCount' => 9,
                'expectedCategoryCount' => 2,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository(): CustomerGroupProductRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerGroupProductVisibilityResolved::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder(): CacheBuilderInterface
    {
        return self::getContainer()->get(
            'oro_visibility.visibility.cache.product.customer_group_product_resolved_cache_builder'
        );
    }
}
