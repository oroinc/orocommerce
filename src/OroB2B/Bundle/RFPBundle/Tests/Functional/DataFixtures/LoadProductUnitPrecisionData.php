<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadProductUnitPrecisionData extends AbstractFixture implements DependentFixtureInterface
{
    const PRODUCT_UNIT_PRECISION_1 = 'test.productUnitPrecision.1';
    const PRODUCT_UNIT_PRECISION_2 = 'test.productUnitPrecision.2';
    const PRODUCT_UNIT_PRECISION_3 = 'test.productUnitPrecision.3';
    const PRODUCT_UNIT_PRECISION_4 = 'test.productUnitPrecision.4';

    /**
     * @var array
     */
    protected $products = [
        self::PRODUCT_UNIT_PRECISION_1 => LoadProductData::TEST_PRODUCT_01,
        self::PRODUCT_UNIT_PRECISION_2 => LoadProductData::TEST_PRODUCT_02,
        self::PRODUCT_UNIT_PRECISION_3 => LoadProductData::TEST_PRODUCT_03,
        self::PRODUCT_UNIT_PRECISION_4 => LoadProductData::TEST_PRODUCT_04,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->products as $productUnitPrecisionKey => $productName) {
            $product = $this->getReference($productName);

            $productUnits = $manager->getRepository('OroB2BProductBundle:ProductUnit')->findAll();
            $productUnit = $productUnits[array_rand($productUnits)];

            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPrecision($productUnit->getDefaultPrecision());

            $this->setReference($productUnitPrecisionKey, $productUnitPrecision);
            $manager->persist($productUnitPrecision);
        }

        $manager->flush();
    }
}
