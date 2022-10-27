<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestProductItemTest extends RestJsonApiTestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * @return array
     */
    private function getCreateData()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $requestProduct = $request->getRequestProducts()->first();

        return [
            'data' => [
                'type'          => 'rfqproductitems',
                'attributes'    => [
                    'quantity' => 10,
                    'value'    => 100,
                    'currency' => 'USD'
                ],
                'relationships' => [
                    'productUnit'    => [
                        'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']
                    ],
                    'requestProduct' => [
                        'data' => ['type' => 'rfqproducts', 'id' => (string)$requestProduct->getId()]
                    ]
                ]
            ]
        ];
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'rfqproductitems'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * LoadRequestData::NUM_LINE_ITEMS
            * LoadRequestData::NUM_PRODUCTS;

        $this->assertResponseCount($expectedCount, $response);
    }

    public function testGet()
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestProductItem::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqproductitems', 'id' => $entity->getId()]
        );

        self::assertResponseNotEmpty($response);
    }

    /**
     * @return int
     */
    public function testCreateEntity()
    {
        $data = $this->getCreateData();
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data
        );

        $entityId = $this->getResourceId($response);
        $data['data']['id'] = $entityId;
        $data['data']['attributes']['value'] = '100.0000';
        $this->assertResponseContains($data, $response);

        return (int)$entityId;
    }

    public function testTryToCreateEmptyValue()
    {
        $data = $this->getCreateData();
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateEmptyCurrency()
    {
        $data = $this->getCreateData();
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongValue()
    {
        $data = $this->getCreateData();
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    /**
     * @depends testCreateEntity
     *
     * @param int $entityId
     */
    public function testUpdateEntity($entityId)
    {
        $data = [
            'data' => [
                'id'         => (string)$entityId,
                'type'       => 'rfqproductitems',
                'attributes' => [
                    'value' => 150
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'rfqproductitems', 'id' => $entityId],
            $data
        );

        $result = $this->jsonToArray($response->getContent());

        $this->assertEquals(150, $result['data']['attributes']['value']);
    }

    /**
     * @depends testCreateEntity
     *
     * @param int $entityId
     */
    public function testDeleteEntity($entityId)
    {
        $this->delete(
            ['entity' => 'rfqproductitems', 'id' => $entityId]
        );

        $this->assertNull(
            $this->getEntityManager()->find(RequestProductItem::class, $entityId)
        );
    }
}
