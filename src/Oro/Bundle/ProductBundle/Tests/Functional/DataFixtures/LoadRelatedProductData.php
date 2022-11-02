<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class LoadRelatedProductData extends AbstractFixture implements DependentFixtureInterface
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
    protected static $relatedProducts = [
        'product-3' => ['product-1', 'product-2'],
        'product-4' => ['product-3', 'product-5'],
        'product-5' => ['product-4']
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $manager->getRepository(Product::class);

        foreach (self::$relatedProducts as $sku => $relatedProducts) {
            foreach ($relatedProducts as $relatedProductSku) {
                $product = $productRepository->findOneBySku($sku);
                $relatedProduct = $productRepository->findOneBySku($relatedProductSku);

                $productRelation = new RelatedProduct();
                $productRelation->setProduct($product)->setRelatedItem($relatedProduct);

                $manager->persist($productRelation);

                $this->addReference(
                    sprintf('related-product-%s-%s', $product->getSku(), $relatedProduct->getSku()),
                    $productRelation
                );
            }
        }
        $manager->flush();
    }
}
