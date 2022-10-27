<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadProductVariants extends AbstractFixture implements DependentFixtureInterface
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
        $this->attachProductVariantsToConfigurableProduct(
            $this->getReference(LoadProductData::PRODUCT_8),
            [
                $this->getReference(LoadProductData::PRODUCT_1),
                $this->getReference(LoadProductData::PRODUCT_2),
                $this->getReference(LoadProductData::PRODUCT_3),
            ]
        );

        $manager->flush();
    }

    private function attachProductVariantsToConfigurableProduct(Product $configurableProducts, array $linkedProducts)
    {
        foreach ($linkedProducts as $linkedProduct) {
            $productVariantLink = new ProductVariantLink($configurableProducts, $linkedProduct);
            $configurableProducts->addVariantLink($productVariantLink);
        }
    }
}
