<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestProductItemApiTest extends AbstractRequestApiTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return RequestProductItem::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        $maxCount = LoadRequestData::NUM_REQUESTS * LoadRequestData::NUM_LINE_ITEMS * LoadRequestData::NUM_PRODUCTS;

        return [
            [
                'filters' => [],
                'expectedCount' => $maxCount,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
    }

    public function testCreateEntity()
    {
        $entityType = $this->getEntityType($this->getEntityClass());
        $notValidData = [
            'data' => [
                'type' => $entityType,
                'attributes' => [
                    'quantity' => 10,
                    'value' => 100,
                ],
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $notValidData
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $requestProduct = $request->getRequestProducts()->first();

        $data = [
            'data' => [
                'type' => $entityType,
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

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $result = $this->jsonToArray($response->getContent());

        $data['data']['id'] = $result['data']['id'];
        $this->assertEquals($data, $result);

        return $result['data']['id'];
    }

    /**
     * @depends testCreateEntity
     *
     * @param int $entityId
     */
    public function testUpdateEntity($entityId)
    {
        $entityType = $this->getEntityType($this->getEntityClass());

        $data = [
            'data' => [
                'id' => $entityId,
                'type' => $entityType,
                'attributes' => [
                    'value' => 150
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch',
                ['entity' => $entityType, 'id' => (string)$entityId]
            ),
            $data
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_OK);
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
        $entityType = $this->getEntityType($this->getEntityClass());

        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_api_delete', ['entity' => $entityType, 'id' => $entityId])
        );

        $this->assertDeletedEntity($response, $entityId);
    }
}
