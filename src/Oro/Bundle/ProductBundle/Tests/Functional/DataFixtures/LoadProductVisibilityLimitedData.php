<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadProductVisibilityLimitedData extends AbstractFixture implements DependentFixtureInterface
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
        return ['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $i = 1;
        foreach (self::$products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $manager->persist($product);
        }
        $manager->flush();
    }
}
