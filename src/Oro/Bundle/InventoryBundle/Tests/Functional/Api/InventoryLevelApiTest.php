<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\ApiResponseContentTrait;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadWarehousesInventoryLevelWithPrimaryUnit;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class InventoryLevelApiTest extends RestJsonApiTestCase
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
        $entityType = $this->getEntityType(InventoryLevel::class);

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
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'warehouse_inventory_level.%s',
                'product_unit_precision.product.1.liter'
            )
        );
        $this->assertEquals('10', $inventoryLevel->getQuantity());

        $entityType = $this->getEntityType(InventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => $inventoryLevel->getProduct()->getSku(),
                'attributes' =>
                [
                    'quantity' => 17,
                    'unit' => $inventoryLevel->getProductUnitPrecision()->getProductUnitCode(),
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
        $entityType = $this->getEntityType(InventoryLevel::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => 'product.1',
                'attributes' =>
                    [
                        'quantity' => 1,
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
        $entityType = $this->getEntityType(InventoryLevel::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['quantity' => 100],
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => $this->getEntityType(Product::class),
                            'id' => 'product.3',
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
        $this->assertCreatedInventoryLevel('product.3', 'liter', 100);
    }

    public function testCreateEntityWithDefaultUnit()
    {
        $entityType = $this->getEntityType(InventoryLevel::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['quantity' => 50],
                'relationships' => [
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
        $this->assertCreatedInventoryLevel('product.2', null, 50);
    }

    public function testDeleteEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'warehouse_inventory_level.%s',
                'product_unit_precision.product.1.bottle'
            )
        );

        $entityType = $this->getEntityType(InventoryLevel::class);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', ['entity' => $entityType, 'id' => $inventoryLevel->getId()])
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        $this->assertDeletedInventorLevel($inventoryLevel->getId());
    }

    public function testDeleteEntityUsingFilters()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'warehouse_inventory_level.%s',
                'product_unit_precision.product.1.liter'
            )
        );

        $params = [
            'filter' => [
                'product.sku' => $inventoryLevel->getProduct()->getSku(),
                'productUnitPrecision.unit.code' => $inventoryLevel->getProductUnitPrecision()->getProductUnitCode(),
            ]
        ];

        $entityType = $this->getEntityType(InventoryLevel::class);
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_cdelete', ['entity' => $entityType]),
            $params
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        $this->assertDeletedInventorLevel($inventoryLevel->getId());
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
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $doctrineHelper->getEntity(InventoryLevel::class, $inventoryLevelId);

        $this->assertEquals($quantity, $result['data']['attributes']['quantity']);
        $this->assertEquals($quantity, $inventoryLevel->getQuantity());
    }

    /**
     * @param string $productSku
     * @param string|null $unit
     * @param int $quantity
     */
    protected function assertCreatedInventoryLevel($productSku, $unit, $quantity)
    {
        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        $productUnitPrecisionRepository = $this->doctrineHelper->getEntityRepository(ProductUnitPrecision::class);
        $inventoryLevelRepository = $doctrineHelper->getEntityRepository(InventoryLevel::class);

        /** @var Product $product */
        $product = $productRepository->findOneBy(['sku' => $productSku]);
        /** @var ProductUnitPrecision $productUnitPrecision */
        $productUnitPrecision = $unit
            ? $productUnitPrecisionRepository->findOneBy(['product' => $product, 'unit' => $unit])
            : $product->getPrimaryUnitPrecision();
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $inventoryLevelRepository->findOneBy(
            [
                'product' => $product->getId(),
                'productUnitPrecision' => $productUnitPrecision->getId(),
            ]
        );

        $this->assertInstanceOf(InventoryLevel::class, $inventoryLevel);
        $this->assertEquals($quantity, $inventoryLevel->getQuantity());
    }

    /**
     * @param int $inventoryLevelId
     */
    protected function assertDeletedInventorLevel($inventoryLevelId)
    {
        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        $inventoryLevelRepository = $doctrineHelper->getEntityRepository(InventoryLevel::class);
        $result = $inventoryLevelRepository->findOneBy(['id' => $inventoryLevelId]);
        $this->assertNull($result);
    }
}
