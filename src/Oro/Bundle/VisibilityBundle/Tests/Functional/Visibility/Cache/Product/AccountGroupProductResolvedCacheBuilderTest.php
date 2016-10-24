<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AccountGroupProductResolvedCacheBuilder;

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
                'expectedStaticCount' => 6,
                'expectedCategoryCount' => 2,
            ],
        ];
    }

    /**
     * @return AccountGroupProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.account_group_product_repository_holder')->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new AccountGroupProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_scope.scope_manager')
        );
        $builder->setCacheClass(
            $container->getParameter('oro_visibility.entity.account_group_product_visibility_resolved.class')
        );
        $builder->setRepositoryHolder($container->get('oro_visibility.account_group_product_repository_holder'));

        return $builder;
    }
}
