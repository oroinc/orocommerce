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
                return $this->getProductUnit($reference)->getCode();
            },
            $expectedUnitReferences
        );

        $units = array_map(
            function (ProductUnit $unit) {
                return $unit->getCode();
            },
            $units
        );

        $this->assertEquals($expectedUnits, $units);
    }

    /**
     * @param array $expectedData
     *
     * @dataProvider getAllUnitsProvider
     */
    public function testGetAllUnits(array $expectedData)
    {
        $units = $this->getRepository()->getAllUnits();

        $units = array_map(
            function (ProductUnit $unit) {
                return $unit->getCode();
            },
            $units
        );

        $this->assertEquals($expectedData, $units);
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
    {
        return [
            ['product.1', ['product_unit.bottle', 'product_unit.liter']],
            ['product.2', ['product_unit.bottle', 'product_unit.box', 'product_unit.liter']]
        ];
    }

    /**
     * @return array
     */
    public function getAllUnitsProvider()
    {
        return [
            [
                'expected' => [
                    'bottle',
                    'box',
                    'each',
                    'hour',
                    'item',
                    'kg',
                    'liter',
                    'piece',
                    'set',
                ],
            ],
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
            $this->getContainer()->getParameter('orob2b_product.entity.product_unit.class')
        );
    }
}
