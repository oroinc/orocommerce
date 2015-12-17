<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;

class LoadProductTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';
    const TAX_3 = 'TAX3';

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
        $this->createProductTaxCode(
            $manager,
            self::TAX_1,
            'Tax description 1',
            [LoadProductData::PRODUCT_1, LoadProductData::PRODUCT_2]
        );
        $this->createProductTaxCode($manager, self::TAX_2, 'Tax description 2', [LoadProductData::PRODUCT_3]);
        $this->createProductTaxCode($manager, self::TAX_3, 'Tax description 3', []);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param string $description
     * @param array $productRefs
     * @return ProductTaxCode
     */
    protected function createProductTaxCode(ObjectManager $manager, $code, $description, $productRefs)
    {
        $productTaxCode = new ProductTaxCode();
        $productTaxCode->setCode($code);
        $productTaxCode->setDescription($description);
        foreach ($productRefs as $productRef) {
            /** @var Product $product */
            $product = $this->getReference($productRef);
            $productTaxCode->addProduct($product);
        }

        $manager->persist($productTaxCode);
        $this->addReference('product_tax_code.' . $code, $productTaxCode);

        return $productTaxCode;
    }
}
