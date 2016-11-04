<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\Product;

class LoadFrontendProductVisibilityData extends ContainerAwareFixture implements DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductVisibilityData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->container->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->container->get('oro_website_search.indexer')->reindex(Product::class);
    }
}
