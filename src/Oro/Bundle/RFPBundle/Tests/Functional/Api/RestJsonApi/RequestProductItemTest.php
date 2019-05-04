<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestProductItemTest extends RestJsonApiTestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'requestproductitems'],
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
            ['entity' => 'requestproductitems', 'id' => $entity->getId()]
        );

        $this->assertResponseNotEmpty($response);
    }

    /**
     * @return int
     */
    public function testCreateEntity()
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $requestProduct = $request->getRequestProducts()->first();

        $data = [
            'data' => [
                'type' => 'requestproductitems',
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
                        'data' => ['type' => 'requestproducts', 'id' => (string)$requestProduct->getId()]
                    ]
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'requestproductitems'],
            $data
        );

        $entityId = $this->getResourceId($response);
        $data['data']['id'] = $entityId;
        $data['data']['attributes']['value'] = '100.0000';
        $this->assertResponseContains($data, $response);

        return (int)$entityId;
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
                'id' => (string)$entityId,
                'type' => 'requestproductitems',
                'attributes' => [
                    'value' => 150
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'requestproductitems', 'id' => $entityId],
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
            ['entity' => 'requestproductitems', 'id' => $entityId]
        );

        $this->assertNull(
            $this->getEntityManager()->find(RequestProductItem::class, $entityId)
        );
    }
}
