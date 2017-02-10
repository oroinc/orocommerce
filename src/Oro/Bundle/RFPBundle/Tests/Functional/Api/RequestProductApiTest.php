<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestProductApiTest extends AbstractRequestApiTest
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
        return RequestProduct::class;
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
                    'comment' => 'Test'
                ],
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $notValidData
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);

        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $validData = [
            'data' => [
                'type' => $entityType,
                'attributes' => [
                    'comment' => 'Test'
                ],
                'relationships' => [
                    'request' => [
                        'data' => ['type' => $this->getEntityType(Request::class), 'id' => (string)$request->getId()]
                    ],
                    'product' => [
                        'data' => ['type' => $this->getEntityType(Product::class), 'id' => (string)$product->getId()]
                    ],
                    'requestProductItems' => [
                        'data' => [
                            [
                                'type' => $this->getEntityType(RequestProductItem::class),
                                'id' => (string)$requestProductItem->getId()
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $validData
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $result = $this->jsonToArray($response->getContent());
        $entityId = $result['data']['id'];
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->doctrineHelper->getEntity($this->getEntityClass(), $entityId);
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntity(Product::class, $product->getId());

        $this->assertInstanceOf(RequestProduct::class, $requestProduct);
        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());

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
                    'comment' => 'Test2'
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

        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->doctrineHelper->getEntity($this->getEntityClass(), $entityId);
        $this->assertEquals('Test2', $requestProduct->getComment());
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
