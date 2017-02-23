<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestApiTest extends AbstractRequestApiTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass()
    {
        return Request::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        return [
            [
                'filters' => [],
                'expectedCount' => LoadRequestData::NUM_REQUESTS,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
    }

    /**
     * @param array $filters
     * @param int $expectedCount
     * @param array $params
     * @param array $expectedContent
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(array $filters, $expectedCount, array $params = [], array $expectedContent = null)
    {
        parent::testCgetEntity($filters, $expectedCount, $params, $expectedContent);
    }

    public function testUpdateEntity()
    {
        /** @var Request $requestEntity */
        $requestEntity = $this->getReference('rfp.request.1');
        $oldRequestEntity = clone $requestEntity;


        $entityType = $this->getEntityType($this->getEntityClass());
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$requestEntity->getId(),
                'attributes' => [
                    'firstName' => 'Ronald',
                    'lastName' => 'Rivera',
                    'company' => 'Centidel',
                    'phone' => '2-(999)507-4625',
                    'poNumber' => 'CA3009USD',
                    'createdAt' => '1970-01-01T00:00:00Z',
                    'updatedAt' => '1970-01-01T00:00:00Z'
                ],
                'relationships' => [
                    'customer_status' => [
                        'data' => ['type' => 'rfpcustomerstatuses', 'id' => 'requires_attention']
                    ],
                    'internal_status' => [
                        'data' => ['type' => 'rfpinternalstatuses', 'id' => 'cancelled_by_customer']
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => $requestEntity->getId()]
            ),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedRequest($oldRequestEntity, $result, $data);
    }

    /**
     * @param Request $oldRequest
     * @param array $result
     * @param array $data
     */
    protected function assertUpdatedRequest(Request $oldRequest, array $result, array $data)
    {
        /** @var Request $newRequest */
        $newRequest = $this->doctrineHelper->getEntity($this->getEntityClass(), $data['data']['id']);

        $attributes = $data['data']['attributes'];
        unset($attributes['createdAt'], $attributes['updatedAt']);

        foreach ($attributes as $name => $attribute) {
            $this->assertEquals($result['data']['attributes'][$name], $attribute);
        }

        $this->assertEquals(
            $newRequest->getInternalStatus()->getId(),
            $oldRequest->getInternalStatus()->getId()
        );

        $this->assertEquals(
            $newRequest->getCustomerStatus()->getId(),
            $oldRequest->getCustomerStatus()->getId()
        );
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $entityType = $this->getEntityType($this->getEntityClass());
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $product = (string)$product->getId();

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');

        $data = [
            'data' => [
                'id' => '8da4d8e6',
                'type' => $entityType,
                'attributes' => [
                    'company' => 'Oro',
                    'firstName' => 'Ronald',
                    'lastName' => 'Rivera',
                    'email' => 'test@example.com'
                ],
                'relationships' => [
                    'requestProducts' => [
                        'data' => [
                            [
                                'type' => $this->getEntityType(RequestProduct::class),
                                'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f'
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f',
                    'type' => $this->getEntityType(RequestProduct::class),
                    'attributes' => [
                        'comment' => 'Test'
                    ],
                    'relationships' => [
                        'request' => [
                            'data' => ['type' => $this->getEntityType(Request::class), 'id' => '8da4d8e6']
                        ],
                        'product' => [
                            'data' => ['type' => $this->getEntityType(Product::class), 'id' => $product]
                        ],
                        'requestProductItems' => [
                            'data' => [
                                [
                                    'type' => $this->getEntityType(RequestProductItem::class),
                                    'id' => '707dda0d-35f5-47b9-b2ce-a3e92b9fdee7'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => '707dda0d-35f5-47b9-b2ce-a3e92b9fdee7',
                    'type' => $this->getEntityType(RequestProductItem::class),
                    'attributes' => [
                        'quantity' => 10,
                        'value' => 100,
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => ['type' => 'productunits', 'id' => $productUnit->getCode()]
                        ],
                        'requestProduct' => [
                            'data' => [
                                'type' => $this->getEntityType(RequestProduct::class),
                                'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );


        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $result = $this->jsonToArray($response->getContent());

        $this->assertEquals($data['data']['attributes']['firstName'], $result['data']['attributes']['firstName']);

        return $result['data']['id'];
    }

    /**
     * @depends testCreate
     *
     * @param int $entityId
     */
    public function testDeleteEntity($entityId)
    {
        $entityType = $this->getEntityType($this->getEntityClass());

        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', ['entity' => $entityType, 'id' => $entityId])
        );

        $this->assertDeletedEntity($response, $entityId);
    }
}
