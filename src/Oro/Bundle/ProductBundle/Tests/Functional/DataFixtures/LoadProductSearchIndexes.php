<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ProductBundle\Entity\Product;

class LoadProductSearchIndexes extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $products = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_4,
        LoadProductData::PRODUCT_5,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8
    ];

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$products as $productReferenceKey) {
            /** @var Product $product */
            $product = $this->getReference($productReferenceKey);
            $this->indexProduct($product);
        }
    }

    /**
     * @param Product $product
     */
    protected function indexProduct(Product $product)
    {
        $this->container->get('oro_website_search.indexer')->save($product);
    }
}
