<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductName;

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
        $product->addName((new ProductName())->setString('Test Product'));
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
