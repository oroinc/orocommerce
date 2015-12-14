<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;

class LoadProductTaxCodes extends AbstractFixture
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
        $this->createProductTaxCode($manager, 'TAX1', 'Tax description 1', ['product.1', 'product.2']);
        $this->createProductTaxCode($manager, 'TAX2', 'Tax description 2', ['product.3']);
        $this->createProductTaxCode($manager, 'TAX3', 'Tax description 3', []);

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
            $product = $manager->getRepository('OroB2B\Bundle\ProductBundle\Entity\Product')->findOneBySku($productRef);
            $productTaxCode->addProduct($product);
        }

        $manager->persist($productTaxCode);
        $this->addReference('product_tax_code.' . $code, $productTaxCode);

        return $productTaxCode;
    }
}
