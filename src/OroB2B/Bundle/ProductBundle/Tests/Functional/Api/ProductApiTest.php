<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class ProductApiTest extends RestJsonApiTestCase
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
                LoadWarehousesAndInventoryLevels::class,
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param string $expectedStatusCode
     * @param array $params
     * @param array $filters
     * @param int $expectedCount
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(
        $entityClass,
        $expectedStatusCode,
        array $params,
        array $filters,
        $expectedCount,
        array $expectedContent = null
    ) {
        $entityType = $this->getEntityType($entityClass);

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

        $this->assertApiResponseStatusCodeEquals($response, $expectedStatusCode, $entityType, 'get list');
        $content = json_decode($response->getContent(), true);
        $this->assertCount($expectedCount, $content['data']);
        if ($expectedContent) {
            $this->assertIsContained($expectedContent, $content['data']);
        }
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
                'entityClass' => Product::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'sku',
                        'references' => ['product.1']
                    ],
                ],
                'expectedCount' => 1,
                'expectedContent' => [
                    [
                        'type' => 'products',
                        'attributes' => [
                            'sku' => 'product.1',
                        ],
                        'relationships' => [
                            'inventory_status' => [
                                'data' => [
                                    'type' => 'prodinventorystatuses',
                                    'id' => 'in_stock',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'filter by Products with different inventory status' => [
                'entityClass' => Product::class,
                'statusCode' => 200,
                'params' => [],
                'filter' => [
                    [
                        'method' => 'getSku',
                        'key' => 'sku',
                        'references' => ['product.2', 'product.3']
                    ],
                ],
                'expectedCount' => 2,
                'expectedContent' => [
                    [
                        'type' => 'products',
                        'attributes' => [
                            'sku' => 'product.2',
                        ],
                        'relationships' => [
                            'inventory_status' => [
                                'data' => [
                                    'type' => 'prodinventorystatuses',
                                    'id' => 'in_stock',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'products',
                        'attributes' => [
                            'sku' => 'product.3',
                        ],
                        'relationships' => [
                            'inventory_status' => [
                                'data' => [
                                    'type' => 'prodinventorystatuses',
                                    'id' => 'out_of_stock',
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
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $entityType = $this->getEntityType(Product::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => LoadProductData::PRODUCT_1,
                'relationships' => [
                    'inventory_status' => [
                        'data' => [
                            'type' => 'prodinventorystatuses',
                            'id' => 'out_of_stock',
                        ],
                    ],
                ],
            ]
        ];
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => LoadProductData::PRODUCT_1]
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals('out_of_stock', $result['data']['relationships']['inventory_status']['data']['id']);
    }

    /**
     * @param array $expected
     * @param array $content
     */
    protected function assertIsContained(array $expected, array $content)
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $content);
            if (is_array($value)) {
                $this->assertIsContained($value, $content[$key]);
            } else {
                $this->assertEquals($value, $content[$key]);
            }
        }
    }
}
