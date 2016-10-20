<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\CustomerBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 3,
                'expectedCategoryCount' => 24,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE1,
            ],
            'with_website2' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE2,
            ],
        ];
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroCustomerBundle:VisibilityResolved\ProductVisibilityResolved'
        );
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
        $builder = new ProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor'),
            $indexScheduler
        );
        $builder->setCacheClass(
            $container->getParameter('oro_customer.entity.product_visibility_resolved.class')
        );

        return $builder;
    }
}
