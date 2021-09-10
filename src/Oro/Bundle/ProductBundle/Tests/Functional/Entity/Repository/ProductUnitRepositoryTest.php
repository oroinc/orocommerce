<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductUnitRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);
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

    /**
     * @dataProvider getPrimaryProductsUnits
     */
    public function testGetPrimaryProductsUnits(array $products, array $expected)
    {
        $products = array_map(function ($productReference) {
            return $this->getReference($productReference);
        }, $products);
        $productIds = array_map(function (Product $product) {
            return $product->getId();
        }, $products);
        $expectedData = array_combine($productIds, $expected);
        $this->assertEquals($expectedData, $this->getRepository()->getPrimaryProductsUnits($products));
    }

    /**
     * @return array
     */
    public function getPrimaryProductsUnits()
    {
        return [
            [
                'products' => [
                    'product-1',
                    'product-2',
                    'product-3',
                ],
                'expected' => [
                    'product-1' => 'milliliter',
                    'product-2' => 'milliliter',
                    'product-3' => 'milliliter',
                ]
            ]
        ];
    }

    public function testGetProductsUnitsNoQuery()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnit::class);
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
                    'product-1',
                    'product-2',
                    'product-3',
                ],
                'expectedData' => [
                    'product-1' => [
                        'milliliter' => 0,
                        'bottle' => 2,
                        'liter' => 3,
                    ],
                    'product-2' => [
                        'milliliter' => 0,
                        'bottle' => 1,
                        'box' => 1,
                        'liter' => 3,
                    ],
                    'product-3' => [
                        'milliliter' => 0,
                        'liter' => 3,
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider getProductsUnitsByCodesDataProvider
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
                    'product-1',
                    'product-2',
                    'product-3',
                ],
                'codes' => [
                    'bottle',
                    'milliliter',
                ],
                'expectedData' => [
                    'bottle',
                    'milliliter',
                ],
            ],
            [
                'products' => [
                    'product-1',
                    'product-3',
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
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnit::class);
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->assertEquals([], $this->getRepository()->getProductsUnitsByCodes([], []));

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
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
     * @dataProvider getAllUnitsProvider
     */
    public function testGetAllUnitCodes(array $expectedData)
    {
        $this->assertEquals($expectedData, $this->getRepository()->getAllUnitCodes());
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
    {
        return [
            ['product-1', ['product_unit.bottle', 'product_unit.liter', 'product_unit.milliliter']],
            ['product-2', ['product_unit.bottle', 'product_unit.box', 'product_unit.liter', 'product_unit.milliliter']]
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
        return $this->getContainer()->get('doctrine')->getRepository(ProductUnit::class);
    }
}
