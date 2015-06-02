<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

/**
 * @dbIsolation
 */
class ProductUnitRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);
    }

    /**
     * @param string $productReference
     * @param string[] $expectedUnitReferences
     *
     * @dataProvider productUnitsDataProvider
     */
    public function testGetProductUnits($productReference, array $expectedUnitReferences)
    {
        $product = $this->getProduct($productReference);

        $units = $this->getRepository()->getProductUnits($product);

        $expectedUnits = array_map(
            function ($reference) {
                return $this->getProductUnit($reference);
            },
            $expectedUnitReferences
        );

        $this->assertEquals($expectedUnits, $units);
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
    {
        return [
            ['product.1', ['product_unit_precision.product.1.bottle', 'product_unit_precision.product.1.liter']],
            ['product.2', ['product_unit_precision.product.1.bottle', 'product_unit_precision.product.1.liter']]
        ];
    }

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     * @return ProductUnit
     */
    protected function getProductUnit($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.product_unit.class')
        );
    }
}
