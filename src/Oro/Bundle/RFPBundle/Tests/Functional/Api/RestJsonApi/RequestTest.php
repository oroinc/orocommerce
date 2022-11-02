<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'rfqs'],
            ['page' => ['size' => 100]]
        );

        $this->assertResponseCount(LoadRequestData::NUM_REQUESTS, $response);
    }

    public function testGet()
    {
        $entity = $this->getEntityManager()
            ->getRepository(Request::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqs', 'id' => $entity->getId()]
        );

        $this->assertResponseNotEmpty($response);
    }

    public function testUpdateEntity()
    {
        /** @var Request $requestEntity */
        $requestEntity = $this->getReference('rfp.request.1');
        $oldRequestEntity = clone $requestEntity;

        $data = [
            'data' => [
                'type' => 'rfqs',
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
                        'data' => ['type' => 'rfqcustomerstatuses', 'id' => 'requires_attention']
                    ],
                    'internal_status' => [
                        'data' => ['type' => 'rfqinternalstatuses', 'id' => 'cancelled_by_customer']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'rfqs', 'id' => $requestEntity->getId()],
            $data
        );

        $result = $this->jsonToArray($response->getContent());
        $this->assertUpdatedRequest($oldRequestEntity, $result, $data);
    }

    protected function assertUpdatedRequest(Request $oldRequest, array $result, array $data)
    {
        /** @var Request $newRequest */
        $newRequest = $this->getEntityManager()->find(Request::class, $data['data']['id']);

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
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $product = (string)$product->getId();

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');

        $data = [
            'data' => [
                'id' => '8da4d8e6',
                'type' => 'rfqs',
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
                                'type' => 'rfqproducts',
                                'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f'
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f',
                    'type' => 'rfqproducts',
                    'attributes' => [
                        'comment' => 'Test'
                    ],
                    'relationships' => [
                        'request' => [
                            'data' => ['type' => 'rfqs', 'id' => '8da4d8e6']
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => $product]
                        ],
                        'requestProductItems' => [
                            'data' => [
                                [
                                    'type' => 'rfqproductitems',
                                    'id' => '707dda0d-35f5-47b9-b2ce-a3e92b9fdee7'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => '707dda0d-35f5-47b9-b2ce-a3e92b9fdee7',
                    'type' => 'rfqproductitems',
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
                                'type' => 'rfqproducts',
                                'id' => '8da4d8e7-6b25-4c5c-8075-b510f7bbb84f'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'rfqs'],
            $data
        );

        $result = $this->jsonToArray($response->getContent());

        $this->assertEquals($data['data']['attributes']['firstName'], $result['data']['attributes']['firstName']);

        return (int)$result['data']['id'];
    }

    /**
     * @depends testCreate
     *
     * @param int $entityId
     */
    public function testDeleteEntity($entityId)
    {
        $this->delete(
            ['entity' => 'rfqs', 'id' => $entityId]
        );

        $this->assertNull(
            $this->getEntityManager()->find(Request::class, $entityId)
        );
    }
}
