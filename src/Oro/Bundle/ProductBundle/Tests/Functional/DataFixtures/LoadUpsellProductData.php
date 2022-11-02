<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class LoadUpsellProductData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class
        ];
    }

    /**
     * @var array
     */
    protected static $upsellProducts = [
        'product-3' => ['product-1', 'product-2'],
        'product-4' => ['product-3'],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $manager->getRepository(Product::class);

        foreach (self::$upsellProducts as $sku => $upsellProducts) {
            foreach ($upsellProducts as $upsellProductSku) {
                $product = $productRepository->findOneBySku($sku);
                $upsellProduct = $productRepository->findOneBySku($upsellProductSku);

                $productRelation = new UpsellProduct();
                $productRelation->setProduct($product)->setRelatedItem($upsellProduct);

                $manager->persist($productRelation);
            }
        }
        $manager->flush();
    }
}
