<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\CustomerGroupProductResolvedCacheBuilder;

class CustomerGroupProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            [
                'expectedStaticCount' => 9,
                'expectedCategoryCount' => 2,
            ],
        ];
    }

    /**
     * @return CustomerGroupProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.customer_group_product_repository');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $indexScheduler = new ProductIndexScheduler(
            $container->get('oro_entity.doctrine_helper'),
            $container->get('event_dispatcher')
        );

        $builder = new CustomerGroupProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_scope.scope_manager'),
            $indexScheduler,
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('oro_visibility.entity.customer_group_product_visibility_resolved.class')
        );
        $builder->setRepository($container->get('oro_visibility.customer_group_product_repository'));

        return $builder;
    }
}
