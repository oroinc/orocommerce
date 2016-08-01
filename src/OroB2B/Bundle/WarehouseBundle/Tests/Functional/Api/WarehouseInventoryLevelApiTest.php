<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Functional\Api\ApiResponseContentTrait;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesInventoryLevelWithPrimaryUnit;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class WarehouseInventoryLevelApiTest extends RestJsonApiTestCase
{
    use ApiResponseContentTrait;

    const ARRAY_DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadWarehousesInventoryLevelWithPrimaryUnit::class]);
    }

    /**
     * @param array $filters
     * @param int $expectedCount
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(array $filters, $expectedCount, array $expectedContent)
    {
        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);

        $params = ['include' => 'product,productUnitPrecision'];
        foreach ($filters as $filter) {
            $filterValue = '';
            foreach ($filter['references'] as $value) {
                $method = $filter['method'];
                $filterValue .= $this->getReference($value)->$method() . self::ARRAY_DELIMITER;
            }
            $params['filter'][$filter['key']] = substr($filterValue, 0, -1);
        }

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityType]),
            $params
        );

        $this->assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);

        $expectedContent = $this->addReferenceRelationshipsAndAssertIncluded($expectedContent, $content['included']);
        $this->assertIsContained($expectedContent, $content['data']);
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
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1']
                    ],
                ],
                'expectedCount' => 3,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.milliliter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'milliliter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products' => [
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                ],
                'expectedCount' => 7,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.box',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'box',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.box',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'box',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.milliliter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'milliliter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Warehouse' => [
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
                'expectedCount' => 1,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.box',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'box',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Unit' => [
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
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products and Units' => [
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
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Unit' => [
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
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouse and Units' => [
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
                'expectedCount' => 4,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products, Warehouses and Units' => [
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'product.sku',
                        'references' => ['product.1', 'product.2']
                    ],
                    [
                        'method' => 'getId',
                        'key' => 'warehouse',
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
                'expectedCount' => 6,
                'expectedContent' => [
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 10,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 99,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.1',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.1',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.1.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 12.345,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.liter',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'liter',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.bottle',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'bottle',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 42,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.box',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'box',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'warehouseinventorylevels',
                        'attributes' => [
                            'quantity' => 98.765,
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product.2',
                                    ],
                                ],
                                'included' => [
                                    'attributes' => [
                                        'sku' => 'product.2',
                                    ],
                                ],
                            ],
                            'productUnitPrecision' => [
                                'data' => [
                                    'type' => 'productunitprecisions',
                                ],
                                'references' => [
                                    'product' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => 'product_unit_precision.product.2.box',
                                    ],
                                ],
                                'included' => [
                                    'relationships' => [
                                        'unit' => [
                                            'data' => [
                                                'id' => 'box',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'warehouse' => [
                                'data' => [
                                    'type' => 'warehouses',
                                ],
                                'references' => [
                                    'warehouse' => [
                                        'key' => 'id',
                                        'method' => 'getId',
                                        'reference' => LoadWarehousesAndInventoryLevels::WAREHOUSE2,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var WarehouseInventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'warehouse_inventory_level.%s.%s',
                LoadWarehousesAndInventoryLevels::WAREHOUSE1,
                'product_unit_precision.product.1.liter'
            )
        );
        $this->assertEquals('10', $inventoryLevel->getQuantity());

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => $inventoryLevel->getProduct()->getSku(),
                'attributes' =>
                [
                    'quantity' => 17,
                    'unit' => $inventoryLevel->getProductUnitPrecision()->getProductUnitCode(),
                    'warehouse' => $inventoryLevel->getWarehouse()->getId(),
                ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => $inventoryLevel->getProduct()->getSku()]
            ),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $inventoryLevel->getId(), 17);
    }

    public function testUpdateEntityWithDefaultUnit()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => 'product.1',
                'attributes' =>
                    [
                        'quantity' => 1,
                        'warehouse' => $warehouse->getId(),
                    ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => 'product.1']
            ),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $result['data']['id'], 1);
    }

    public function testCreateEntity()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE2);
        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['quantity' => 100],
                'relationships' => [
                    'warehouse' => [
                        'data' => [
                            'type' => $this->getEntityType(Warehouse::class),
                            'id' => $warehouse->getId(),
                        ],
                    ],
                    'product' => [
                        'data' => [
                            'type' => $this->getEntityType(Product::class),
                            'id' => 'product.1',
                        ],
                    ],
                    'unit' => [
                        'data' => [
                            'type' => $this->getEntityType(ProductUnitPrecision::class),
                            'id' => 'liter',
                        ],
                    ],
                ]
            ]
        ];
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        $this->assertCreatedInventoryLevel($warehouse, 'product.1', 'liter', 100);
    }

    public function testCreateEntityWithDefaultUnit()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);
        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['quantity' => 50],
                'relationships' => [
                    'warehouse' => [
                        'data' => [
                            'type' => $this->getEntityType(Warehouse::class),
                            'id' => $warehouse->getId(),
                        ],
                    ],
                    'product' => [
                        'data' => [
                            'type' => $this->getEntityType(Product::class),
                            'id' => 'product.2',
                        ],
                    ],
                ]
            ]
        ];
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        $this->assertCreatedInventoryLevel($warehouse, 'product.2', null, 50);
    }

    public function testUpdateEntityWithOneWarehouse()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getReferenceRepository()->getManager();
        $em->remove($this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE2));
        $em->flush();

        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => 'product.1',
                'attributes' =>
                    [
                        'quantity' => 100,
                    ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => 'product.1']
            ),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedInventoryLevel($result, $result['data']['id'], 100);
    }

    public function testCreateEntityWithOneWarehouse()
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);
        $entityType = $this->getEntityType(WarehouseInventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['quantity' => 10],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => $this->getEntityType(Product::class),
                            'id' => 'product.3',
                        ],
                    ]
                ]
            ]
        ];
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        $this->assertCreatedInventoryLevel($warehouse, 'product.3', null, 10);
    }

    /**
     * @param array $expectedContent
     * @param array $includedItems
     * @return array
     */
    protected function addReferenceRelationshipsAndAssertIncluded(array $expectedContent, array $includedItems)
    {
        foreach ($expectedContent as $key => $expected) {
            if (array_key_exists('relationships', $expected)) {
                $relationships = [];
                foreach ($expected['relationships'] as $relationshipKey => $relationship) {
                    if (array_key_exists('references', $relationship)) {
                        foreach ($relationship['references'] as $reference) {
                            $method = $reference['method'];
                            $referenceId = $reference['reference'];
                            $relationship['data'][$reference['key']] = $this->getReference($referenceId)->$method();
                        }
                        unset($relationship['references']);
                    }
                    $relationships[$relationshipKey] = $relationship;
                }
                foreach ($relationships as $relationshipKey => $relationship) {
                    if (array_key_exists('included', $relationship)) {
                        foreach ($includedItems as $included) {
                            if ($included['type'] == $relationship['data']['type']
                                && $included['id'] == $relationship['data']['id']
                            ) {
                                $this->assertIsContained($relationship['included'], $included);
                            }
                        }
                        unset($relationships[$relationshipKey]['included']);
                    }
                }
                $expectedContent[$key]['relationships'] = $relationships;
            }
        }

        return $expectedContent;
    }

    /**
     * @param array $result
     * @param int $inventoryLevelId
     * @param int $quantity
     */
    protected function assertUpdatedInventoryLevel(array $result, $inventoryLevelId, $quantity)
    {
        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        /** @var WarehouseInventoryLevel $inventoryLevel */
        $inventoryLevel = $doctrineHelper->getEntity(WarehouseInventoryLevel::class, $inventoryLevelId);

        $this->assertEquals($quantity, $result['data']['attributes']['quantity']);
        $this->assertEquals($quantity, $inventoryLevel->getQuantity());
    }

    /**
     * @param Warehouse $warehouse
     * @param string $productSku
     * @param string|null $unit
     * @param int $quantity
     */
    protected function assertCreatedInventoryLevel(Warehouse $warehouse, $productSku, $unit, $quantity)
    {
        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        $productUnitPrecisionRepository = $this->doctrineHelper->getEntityRepository(ProductUnitPrecision::class);
        $inventoryLevelRepository = $doctrineHelper->getEntityRepository(WarehouseInventoryLevel::class);

        /** @var Product $product */
        $product = $productRepository->findOneBy(['sku' => $productSku]);
        /** @var ProductUnitPrecision $productUnitPrecision */
        $productUnitPrecision = $unit
            ? $productUnitPrecisionRepository->findOneBy(['product' => $product, 'unit' => $unit])
            : $product->getPrimaryUnitPrecision();
        /** @var WarehouseInventoryLevel $inventoryLevel */
        $inventoryLevel = $inventoryLevelRepository->findOneBy(
            [
                'product' => $product->getId(),
                'productUnitPrecision' => $productUnitPrecision->getId(),
                'warehouse' => $warehouse->getId(),
            ]
        );

        $this->assertInstanceOf(WarehouseInventoryLevel::class, $inventoryLevel);
        $this->assertEquals($quantity, $inventoryLevel->getQuantity());
    }
}
