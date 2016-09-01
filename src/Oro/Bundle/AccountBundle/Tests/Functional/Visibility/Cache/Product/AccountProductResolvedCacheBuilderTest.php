<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\AccountProductResolvedCacheBuilder;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 4,
                'expectedCategoryCount' => 1,
                'websiteReference' => null,
            ],
            'with_default_website' => [
                'expectedStaticCount' => 4,
                'expectedCategoryCount' => 0,
                'websiteReference' => 'default',
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 1,
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
     * @return AccountProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new AccountProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('orob2b_account.entity.account_product_visibility_resolved.class')
        );

        return $builder;
    }
}
