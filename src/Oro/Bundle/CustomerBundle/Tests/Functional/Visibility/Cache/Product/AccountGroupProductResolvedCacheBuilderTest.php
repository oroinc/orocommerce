<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use Oro\Bundle\CustomerBundle\Visibility\Cache\Product\AccountGroupProductResolvedCacheBuilder;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 8,
                'expectedCategoryCount' => 2,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 2,
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
     * @return AccountGroupProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroCustomerBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
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

        $builder = new AccountGroupProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor'),
            $indexScheduler
        );
        $builder->setCacheClass(
            $container->getParameter('oro_customer.entity.account_group_product_visibility_resolved.class')
        );

        return $builder;
    }
}
