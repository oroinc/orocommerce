<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels;

/**
 * @dbIsolation
 */
class ProductApiTest extends RestJsonApiTestCase
{
    use ApiResponseContentTrait;

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
        $this->loadFixtures([LoadInventoryLevels::class]);
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
        $entityType = $this->getEntityType(Product::class);

        $params = [];
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

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = $this->jsonToArray($response->getContent());
        $this->assertEquals('out_of_stock', $result['data']['relationships']['inventory_status']['data']['id']);

        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        /** @var Product $product */
        $product = $doctrineHelper->getEntity(Product::class, $product->getId());
        $this->assertEquals('out_of_stock', $product->getInventoryStatus()->getId());
    }
}
