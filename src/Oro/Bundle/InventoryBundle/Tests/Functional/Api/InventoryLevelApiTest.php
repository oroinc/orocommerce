<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * @group CommunityEdition
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class InventoryLevelApiTest extends RestJsonApiTestCase
{
    const ARRAY_DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            UpdateInventoryLevelsQuantities::class,
        ]);
    }

    /**
     * @param array $parameters
     * @param string $expectedContentFile
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(array $parameters, $expectedContentFile)
    {
        $entityType = $this->getEntityType(InventoryLevel::class);
        $response = $this->cget(['entity' => $entityType], $parameters);
        $this->assertResponseContains($expectedContentFile, $response);
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku'],
                    ]
                ],
                'expectedContent'
                => '@OroInventoryBundle/Tests/Functional/DataFixtures/responses/filter_by_product.yml',
            ],
            'filter by Products' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                    ]
                ],
                'expectedContent'
                => '@OroInventoryBundle/Tests/Functional/DataFixtures/responses/filter_by_products.yml',
            ],
            'filter by Products and Unit' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                        'productUnitPrecision.unit.code' => ['@product_unit.bottle->code'],
                    ]
                ],
                'expectedContent'
                => '@OroInventoryBundle/Tests/Functional/DataFixtures/responses/filter_by_products_and_unit.yml',
            ],
            'filter by Products and Units' => [
                'parameters' => [
                    'include' => 'product,productUnitPrecision',
                    'filter' => [
                        'product.sku' => ['@product-1->sku', '@product-2->sku'],
                        'productUnitPrecision.unit.code' => ['@product_unit.bottle->code', '@product_unit.liter->code'],
                    ]
                ],
                'expectedContent'
                => '@OroInventoryBundle/Tests/Functional/DataFixtures/responses/filter_by_products and_units.yml',
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'inventory_level.%s',
                'product_unit_precision.product-1.liter'
            )
        );

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
                'id' => 'product-1',
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
                ['entity' => $entityType, 'id' => 'product-1']
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
                            'id' => 'product-3',
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

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $this->assertCreatedInventoryLevel('product-3', 'liter', 100);
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
                            'id' => 'product-2',
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

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $this->assertCreatedInventoryLevel('product-2', null, 50);
    }

    public function testDeleteEntity()
    {
        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $this->getReference(
            sprintf(
                'inventory_level.%s',
                'product_unit_precision.product-1.bottle'
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
                'inventory_level.%s',
                'product_unit_precision.product-1.liter'
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

    /**
     * @param array $rows
     * @return array
     */
    protected function getInventoryLevelContent(array $rows)
    {
        $content = [];
        foreach ($rows as $row) {
            $content[] = [
                'type' => 'inventorylevels',
                'attributes' => [
                    'quantity' => $row['quantity'],
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
                                'reference' => $row['sku'],
                            ],
                        ],
                        'included' => [
                            'attributes' => [
                                'sku' => $row['sku'],
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
                                'reference' => $row['reference'],
                            ],
                        ],
                        'included' => [
                            'relationships' => [
                                'unit' => [
                                    'data' => [
                                        'id' => $row['id'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $content;
    }
}
