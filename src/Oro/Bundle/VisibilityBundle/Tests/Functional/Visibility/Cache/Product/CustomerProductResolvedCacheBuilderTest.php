<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CustomerProductResolvedCacheBuilder;

class CustomerProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider(): array
    {
        return [
            [
                'expectedStaticCount' => 5,
                'expectedCategoryCount' => 1,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository(): CustomerProductRepository
    {
        return $this->getContainer()->get('oro_visibility.customer_product_repository');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder(): CacheBuilderInterface
    {
        $container = $this->getContainer();

        $productReindexManager = new ProductReindexManager(
            $container->get('event_dispatcher')
        );

        $indexScheduler = new ProductIndexScheduler(
            $container->get('oro_entity.doctrine_helper'),
            $productReindexManager
        );

        $builder = new CustomerProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_scope.scope_manager'),
            $indexScheduler,
            $container->get('oro_entity.orm.insert_from_select_query_executor'),
            $productReindexManager
        );
        $builder->setCacheClass(CustomerProductVisibilityResolved::class);
        $builder->setRepository($container->get('oro_visibility.customer_product_repository'));

        return $builder;
    }
}
