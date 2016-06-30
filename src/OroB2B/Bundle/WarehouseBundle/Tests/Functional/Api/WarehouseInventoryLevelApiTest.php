<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Api;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class WarehouseInventoryLevelApiTest extends RestJsonApiTestCase
{
    const ARRAY_DELIMITER = ',';

    /**
     * @var array
     */
    protected $expectations;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(
            [
                'OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels',
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param string $expectedStatusCode
     * @param array $params
     * @param array $filters
     * @param int $expectedCount
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(
        $entityClass,
        $expectedStatusCode,
        array $params,
        array $filters,
        $expectedCount
    ) {
        $entityType = $this->getEntityType($entityClass);

        foreach ($filters as $filter) {
            $filterValue = '';
            foreach ($filter['references'] as $value) {
                $filterValue .= $this->getReference($value)->$filter['method']() . self::ARRAY_DELIMITER;
            }
            $params['filter'][$filter['key']] = substr($filterValue, 0, -1);
        }

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, $expectedStatusCode, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function cgetParamsAndExpectation()
    {
        return [

            'filter by Product' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1']
                    ],
                ],
                'expectedCount' => 2
            ],
            'filter by Products' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                ],
                'expectedCount' => 6
            ],
            'filter by Products and Warehouse' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE2]
                    ],
                ],
                'expectedCount' => 1
            ],
            'filter by Products and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle']
                    ],
                ],
                'expectedCount' => 2
            ],
            'filter by Products and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.bottle', 'product_unit.liter']
                    ],
                ],
                'expectedCount' => 4
            ],
            'filter by Products, Warehouse and Unit' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter']
                    ],
                ],
                'expectedCount' => 2
            ],
            'filter by Products, Warehouse and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [LoadWarehousesAndInventoryLevels::WAREHOUSE1]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle']
                    ],
                ],
                'expectedCount' => 4
            ],
            'filter by Products, Warehouses and Units' => [
                'entityClass' => WarehouseInventoryLevel::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse.id',
                        'references' => [
                            LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                            LoadWarehousesAndInventoryLevels::WAREHOUSE2
                        ]
                    ],
                    [
                        'method' => 'getCode',
                        'key' => 'productUnitPrecision.unit.code',
                        'references' => ['product_unit.liter', 'product_unit.bottle', 'product_unit.box']
                    ],
                ],
                'expectedCount' => 6
            ],
        ];
    }
}
