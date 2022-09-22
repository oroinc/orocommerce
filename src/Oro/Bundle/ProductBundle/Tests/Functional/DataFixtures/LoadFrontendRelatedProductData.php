<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

class LoadFrontendRelatedProductData extends AbstractFixture implements DependentFixtureInterface
{
    protected static array $relatedProducts = [
        'product30' => ['product10', 'product20'],
        'product10' => [
            'product11',
            'product12',
            'product13',
            'product14',
            'product15',
            'product16',
            'product17',
            'product18',
            'product19',
            'product20',
            'product21',
            'product22',
            'product23',
            'product24',
            'product25',
            'product26',
            'product27',
            'product28',
            'product29',
            'product30',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            '@OroProductBundle/Tests/Functional/DataFixtures/frontend_product_grid_pager_fixture.yml',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$relatedProducts as $sku => $relatedProducts) {
            foreach ($relatedProducts as $relatedProductSku) {
                /** @var Product $product */
                $product = $this->getReference($sku);
                /** @var Product $relatedProduct */
                $relatedProduct = $this->getReference($relatedProductSku);

                $productRelation = new RelatedProduct();
                $productRelation->setProduct($product)->setRelatedItem($relatedProduct);

                $manager->persist($productRelation);

                $this->addReference(
                    sprintf(
                        'related-product-%s-%s',
                        $product->getSku(),
                        $relatedProduct->getSku()
                    ),
                    $productRelation
                );
            }
        }
        $manager->flush();
    }
}
