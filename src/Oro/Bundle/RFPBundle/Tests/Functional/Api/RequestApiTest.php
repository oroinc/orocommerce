<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

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

    public function testCreate()
    {
        $entityType = $this->getEntityType($this->getEntityClass());
        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => [
                    'company' => 'Oro',
                    'firstName' => 'Ronald',
                    'lastName' => 'Rivera',
                    'email' => 'test@example.com'
                ],
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
