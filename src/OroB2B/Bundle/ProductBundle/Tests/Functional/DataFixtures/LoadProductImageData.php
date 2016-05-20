<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductImageData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $products = [
        'product.1',
        'product.2',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $image = new File();
            $image->setFilename($product->getSku());
            $product->setImage($image);
            $manager->persist($image);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
