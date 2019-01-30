<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Loads 4 ProductImage entities
 */
class ProductImageData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $product = new Product();
        $product->setDefaultName('Test Product');
        $product->setSku('TestProduct123');
        $manager->persist($product);
        $manager->flush();
        for ($i = 0; $i <= 3; $i++) {
            $productImage = new ProductImage();
            $productImage->setProduct($product);
            $manager->persist($productImage);
        }

        $manager->flush();
    }
}
