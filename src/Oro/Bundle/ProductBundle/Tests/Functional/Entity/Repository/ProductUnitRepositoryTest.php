<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductUnitRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductUnitPrecisions::class]);
    }

    private function getRepository(): ProductUnitRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(ProductUnit::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnit::class);
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }

    private function getProductUnit(string $reference): ProductUnit
    {
        return $this->getReference($reference);
    }

    /**
     * @dataProvider getProductUnitsDataProvider
     */
    public function testGetProductUnits(string $productReference, array $expectedUnitReferences)
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

    public function getProductUnitsDataProvider(): array
    {
        return [
            ['product-1', ['product_unit.bottle', 'product_unit.liter', 'product_unit.milliliter']],
            ['product-2', ['product_unit.bottle', 'product_unit.box', 'product_unit.liter', 'product_unit.milliliter']]
        ];
    }

    public function testGetProductsUnits()
    {
        $product1 = $this->getProduct('product-1');
        $product2 = $this->getProduct('product-2');
        $product3 = $this->getProduct('product-3');

        $products = [$product1, $product2, $product3];

        $this->assertEquals(
            [
                $product1->getId() => [
                    'milliliter' => 0,
                    'bottle' => 2,
                    'liter' => 3,
                ],
                $product2->getId() => [
                    'milliliter' => 0,
                    'bottle' => 1,
                    'box' => 1,
                    'liter' => 3,
                ],
                $product3->getId() => [
                    'milliliter' => 0,
                    'liter' => 3,
                ]
            ],
            $this->getRepository()->getProductsUnits($products)
        );
    }

    public function testGetPrimaryProductsUnits()
    {
        $product1 = $this->getProduct('product-1');
        $product2 = $this->getProduct('product-2');
        $product3 = $this->getProduct('product-3');

        $products = [$product1, $product2, $product3];

        $this->assertEquals(
            [
                $product1->getId() => 'milliliter',
                $product2->getId() => 'milliliter',
                $product3->getId() => 'milliliter'
            ],
            $this->getRepository()->getPrimaryProductsUnits($products)
        );
    }

    public function testGetProductsUnitsByProductIds()
    {
        $product1 = $this->getProduct('product-1');
        $product2 = $this->getProduct('product-2');
        $product3 = $this->getProduct('product-3');

        $productIds = [$product1->getId(), $product2->getId(), $product3->getId()];

        $this->assertEquals(
            [
                $product1->getId() => ['bottle', 'liter', 'milliliter'],
                $product2->getId() => ['bottle', 'box', 'liter', 'milliliter'],
                $product3->getId() => ['liter', 'milliliter']
            ],
            $this->getRepository()->getProductsUnitsByProductIds($productIds)
        );
    }

    public function testGetProductsUnitsNoQuery()
    {
        $em = $this->getEntityManager();
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->assertSame([], $this->getRepository()->getProductsUnits([]));

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @dataProvider getProductsUnitsByCodesDataProvider
     */
    public function testGetProductsUnitsByCodes(array $products, array $codes, array $expectedData)
    {
        $products = array_map(function ($productReference) {
            return $this->getProduct($productReference);
        }, $products);
        $units = array_map(function ($unitCode) {
            return $this->getProductUnit('product_unit.' . $unitCode);
        }, $codes);

        $expectedData = array_reduce($expectedData, function (array $result, $unitCode) {
            $result[$unitCode] = $this->getProductUnit('product_unit.' . $unitCode);
            return $result;
        }, []);
        $actualData = $this->getRepository()->getProductsUnitsByCodes($products, $units);
        $this->assertEquals($expectedData, $actualData);
    }

    public function getProductsUnitsByCodesDataProvider(): array
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
        $em = $this->getEntityManager();
        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->assertSame([], $this->getRepository()->getProductsUnitsByCodes([], []));

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(0, $queries);

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @dataProvider getAllUnitsDataProvider
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
     * @dataProvider getAllUnitsDataProvider
     */
    public function testGetAllUnitCodes(array $expectedData)
    {
        $this->assertEquals($expectedData, $this->getRepository()->getAllUnitCodes());
    }

    public function getAllUnitsDataProvider(): array
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
}
