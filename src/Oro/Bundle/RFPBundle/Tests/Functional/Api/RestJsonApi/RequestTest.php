<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'rfqs'],
            ['page' => ['size' => 100]]
        );

        self::assertResponseCount(LoadRequestData::NUM_REQUESTS, $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.2->id)>']
        );

        $this->assertResponseContains('get_request.yml', $response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'rfqs'],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => 'new_request_id',
                    'attributes' => [
                        'company' => 'Oro',
                        'firstName' => 'Ronald',
                        'lastName' => 'Rivera',
                        'email' => 'test@example.com'
                    ],
                    'relationships' => [
                        'requestProducts' => [
                            'data' => [
                                ['type' => 'rfqproducts', 'id' => 'request_product_1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'rfqproducts',
                        'id' => 'request_product_1',
                        'attributes' => [
                            'comment' => 'Test'
                        ],
                        'relationships' => [
                            'request' => [
                                'data' => ['type' => 'rfqs', 'id' => 'new_request_id']
                            ],
                            'product' => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'requestProductItems' => [
                                'data' => [
                                    ['type' => 'rfqproductitems', 'id' => 'request_product_item_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'rfqproductitems',
                        'id' => 'request_product_item_1',
                        'attributes' => [
                            'quantity' => 10,
                            'value' => 100,
                            'currency' => 'USD'
                        ],
                        'relationships' => [
                            'productUnit' => [
                                'data' => ['type' => 'productunits', 'id' => '@product_unit.liter->code']
                            ],
                            'requestProduct' => [
                                'data' => ['type' => 'rfqproducts', 'id' => 'request_product_1']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'rfqs',
                    'attributes' => [
                        'company' => 'Oro',
                        'firstName' => 'Ronald',
                        'lastName' => 'Rivera',
                        'email' => 'test@example.com'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $response = $this->post(
            ['entity' => 'rfqs'],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => 'new_request_id',
                    'attributes' => [
                        'company' => 'Oro',
                        'firstName' => 'Ronald',
                        'lastName' => 'Rivera',
                        'email' => 'test@example.com'
                    ],
                    'relationships' => [
                        'customerUser' => [
                            'data' => [
                                'type' => 'customerusers',
                                'id' => '<toString(@rfp-customer1-user1@example.com->id)>'
                            ]
                        ],
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => '<toString(@rfp-customer2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $requestEntityId = $this->getReference('rfp.request.1')->getId();
        $data = [
            'data' => [
                'type' => 'rfqs',
                'id' => (string)$requestEntityId,
                'attributes' => [
                    'firstName' => 'Ronald',
                    'lastName' => 'Rivera',
                    'company' => 'Centidel',
                    'phone' => '2-(999)507-4625',
                    'poNumber' => 'CA3009USD'
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
            ['entity' => 'rfqs', 'id' => (string)$requestEntityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['customer_status']['data']['id'] = 'submitted';
        $expectedData['data']['relationships']['internal_status']['data']['id'] = 'open';
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $requestEntityId = $this->getReference('rfp.request.3')->getId();
        $response = $this->patch(
            ['entity' => 'rfqs', 'id' => (string)$requestEntityId],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$requestEntityId,
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => '<toString(@rfp-customer2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $requestEntityId = $this->getReference('rfp.request.1')->getId();
        $this->delete(
            ['entity' => 'rfqs', 'id' => (string)$requestEntityId]
        );

        $deletedRequestEntity = $this->getEntityManager()->find(Request::class, $requestEntityId);
        self::assertTrue(null === $deletedRequestEntity);
    }
}
