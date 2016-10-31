<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AccountProductResolvedCacheBuilder;

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
            [
                'expectedStaticCount' => 4,
                'expectedCategoryCount' => 1,
            ],
        ];
    }

    /**
     * @return AccountProductRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.account_product_repository_holder')->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new AccountProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_scope.scope_manager')
        );
        $builder->setCacheClass(
            $container->getParameter('oro_visibility.entity.account_product_visibility_resolved.class')
        );
        $builder->setRepositoryHolder($container->get('oro_visibility.account_product_repository_holder'));

        return $builder;
    }
}
