<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

class LoadProductTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';
    const TAX_3 = 'TAX3';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';
    const DESCRIPTION_3 = 'Tax description 3';

    const REFERENCE_PREFIX = 'product_tax_code';

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
        $this->createProductTaxCode(
            $manager,
            self::TAX_1,
            self::DESCRIPTION_1,
            [LoadProductData::PRODUCT_1, LoadProductData::PRODUCT_2]
        );
        $this->createProductTaxCode($manager, self::TAX_2, self::DESCRIPTION_2, [LoadProductData::PRODUCT_3]);
        $this->createProductTaxCode($manager, self::TAX_3, self::DESCRIPTION_3, []);

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
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $productTaxCode);

        return $productTaxCode;
    }
}
