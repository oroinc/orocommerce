<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadProductContentVariants extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTestContentVariant($manager, 'test_product_variant.1', $this->getReference('product-1'));
        $this->createTestContentVariant($manager, 'test_product_variant.2', $this->getReference('product-2'));
        $this->createTestContentVariant($manager, 'test_product_variant.3', $this->getReference('product-3'));
        $this->createTestContentVariant($manager, 'test_product_variant.4');
        $this->createTestContentVariant($manager, 'test_product_variant.5');
        $this->createTestContentVariant($manager, 'test_product_variant.6');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $reference
     * @param Product|null $product
     */
    private function createTestContentVariant(ObjectManager $manager, $reference, Product $product = null)
    {
        $testContentVariant = new TestContentVariant();
        $testContentVariant->setProductPageProduct($product);

        $manager->persist($testContentVariant);
        $this->setReference($reference, $testContentVariant);
    }
}
