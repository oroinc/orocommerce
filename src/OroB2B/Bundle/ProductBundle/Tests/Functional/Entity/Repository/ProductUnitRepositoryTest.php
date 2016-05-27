<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

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
     * @dataProvider getProductsUnitsDataProvider
     * @param array $products
     * @param array $expectedData
     */
    public function testGetProductsUnits(array $products, array $expectedData)
    {
        $products = array_map(function ($productReference) {
            return $this->getReference($productReference);
        }, $products);
        $productIds = array_map(function (Product $product) {
            return $product->getId();
        }, $products);
        $expectedData = array_combine($productIds, $expectedData);
        $this->assertEquals($expectedData, $this->getRepository()->getProductsUnits($products));
    }

    public function testGetProductsUnitsNoQuery()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BMenuBundle:MenuItem');
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->assertEquals([], $this->getRepository()->getProductsUnits([]));

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }
    
    /**
     * @return array
     */
    public function getProductsUnitsDataProvider()
    {
        return [
            [
                'products' => [
                    'product.1',
                    'product.2',
                    'product.3',
                ],
                'expectedData' => [
                    'product.1' => ['bottle', 'milliliter'],
                    'product.2' => ['bottle', 'box', 'milliliter'],
                    'product.3' => ['milliliter']
                ],
            ],
        ];
    }

    /**
     * @dataProvider getProductsUnitsByCodesDataProvider
     * @param array $products
     * @param array $codes
     * @param array $expectedData
     */
    public function testGetProductsUnitsByCodes(array $products, array $codes, array $expectedData)
    {
        $products = array_map(function ($productReference) {
            return $this->getReference($productReference);
        }, $products);
        $units = array_map(function ($unitCode) {
            return $this->getReference('product_unit.' . $unitCode);
        }, $codes);

        $expectedData = array_reduce($expectedData, function (array $result, $unitCode) {
            $result[$unitCode] = $this->getReference('product_unit.' . $unitCode);
            return $result;
        }, []);
        $actualData = $this->getRepository()->getProductsUnitsByCodes($products, $units);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function getProductsUnitsByCodesDataProvider()
    {
        return [
            [
                'products' => [],
                'codes' => [],
                'expectedData' => [],
            ],
            [
                'products' => [
                    'product.1',
                    'product.2',
                    'product.3',
                ],
                'codes' => [
                    'liter',
                    'box',
                ],
                'expectedData' => [
                    'box'
                ],
            ],
            [
                'products' => [
                    'product.1',
                    'product.3',
                ],
                'codes' => [
                    'box',
                ],
                'expectedData' => [],
            ],
        ];
    }

    public function testGetProductsUnitsByCodesNoQuery()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BMenuBundle:MenuItem');
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->assertEquals([], $this->getRepository()->getProductsUnitsByCodes([], []));

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
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
            ['product.1', ['product_unit.bottle', 'product_unit.liter', 'product_unit.milliliter']],
            ['product.2', ['product_unit.bottle', 'product_unit.box', 'product_unit.liter', 'product_unit.milliliter']]
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
                    'milliliter',
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
