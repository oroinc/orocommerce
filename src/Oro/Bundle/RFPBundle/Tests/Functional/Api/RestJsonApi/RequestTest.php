<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    public function testGetListFilteredAndSortedByCreatedAt(): void
    {
        /** @var \DateTime $startDate */
        $startDate = $this->getReference(LoadRequestData::REQUEST1)->getCreatedAt();

        $response = $this->cget(
            ['entity' => 'rfqs'],
            [
                'page[size]' => 100,
                'filter[createdAt][gte]' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'sort' => 'createdAt'
            ]
        );

        self::assertResponseCount(LoadRequestData::NUM_REQUESTS, $response);
    }

    public function testGetListFilteredAndSortedByUpdatedAt(): void
    {
        /** @var \DateTime $startDate */
        $startDate = $this->getReference(LoadRequestData::REQUEST1)->getUpdatedAt();

        $response = $this->cget(
            ['entity' => 'rfqs'],
            [
                'page[size]' => 100,
                'filter[updatedAt][gte]' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'sort' => 'updatedAt'
            ]
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
            'create_request.yml'
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

    public function testCreateWithProjectName(): void
    {
        $data = $this->getRequestData('create_request.yml');
        $data['data']['attributes']['projectName'] = 'Some Project';
        $response = $this->post(
            ['entity' => 'rfqs'],
            $data
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'rfqs',
                    'attributes' => [
                        'projectName' => 'Some Project'
                    ]
                ]
            ],
            $response
        );

        $entityId = (int)$this->getResourceId($response);
        $entity = $this->getEntityManager()->find(Request::class, $entityId);
        self::assertEquals('Some Project', $entity->getProjectName());
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

    public function testTryToCreateWithRequestProductFromAnotherRequest(): void
    {
        /** @var Request $request1 */
        $request1 = $this->getReference(LoadRequestData::REQUEST1);
        /** @var RequestProduct $requestProduct1 */
        $requestProduct1 = $request1->getRequestProducts()->first();

        $response = $this->post(
            ['entity' => 'rfqs'],
            [
                'data' => [
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
                                ['type' => 'rfqproducts', 'id' => (string)$requestProduct1->getId()]
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/requestProducts/data/0']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $entityId = $this->getReference(LoadRequestData::REQUEST1)->getId();
        $data = [
            'data' => [
                'type' => 'rfqs',
                'id' => (string)$entityId,
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
            ['entity' => 'rfqs', 'id' => (string)$entityId],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['customer_status']['data']['id'] = 'submitted';
        $expectedData['data']['relationships']['internal_status']['data']['id'] = 'open';
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $entityId = $this->getReference('rfp.request.3')->getId();
        $response = $this->patch(
            ['entity' => 'rfqs', 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$entityId,
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

    public function testTryToUseRequestProductFromAnotherRequest(): void
    {
        /** @var Request $request1 */
        $request1 = $this->getReference(LoadRequestData::REQUEST1);
        /** @var RequestProduct $requestProduct1 */
        $requestProduct1 = $request1->getRequestProducts()->first();
        $entityId = $request1->getId();

        /** @var Request $request2 */
        $request2 = $this->getReference(LoadRequestData::REQUEST2);
        /** @var RequestProduct $requestProduct2 */
        $requestProduct2 = $request2->getRequestProducts()->first();

        $response = $this->patch(
            ['entity' => 'rfqs', 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$entityId,
                    'relationships' => [
                        'requestProducts' => [
                            'data' => [
                                ['type' => 'rfqproducts', 'id' => (string)$requestProduct1->getId()],
                                ['type' => 'rfqproducts', 'id' => (string)$requestProduct2->getId()]
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/requestProducts/data/1']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $entityId = $this->getReference(LoadRequestData::REQUEST1)->getId();
        $this->delete(
            ['entity' => 'rfqs', 'id' => (string)$entityId]
        );

        $deletedRequestEntity = $this->getEntityManager()->find(Request::class, $entityId);
        self::assertTrue(null === $deletedRequestEntity);
    }
}
