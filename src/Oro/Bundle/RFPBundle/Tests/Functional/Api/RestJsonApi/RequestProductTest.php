<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestProductTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'requestproducts'],
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
            ->getRepository(RequestProduct::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'requestproducts', 'id' => $entity->getId()]
        );

        $this->assertResponseNotEmpty($response);
    }

    /**
     * @return int
     */
    public function testCreateEntity()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);

        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $data = [
            'data' => [
                'type' => 'requestproducts',
                'attributes' => [
                    'comment' => 'Test'
                ],
                'relationships' => [
                    'request' => [
                        'data' => ['type' => 'requests', 'id' => (string)$request->getId()]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string)$product->getId()]
                    ],
                    'requestProductItems' => [
                        'data' => [
                            [
                                'type' => 'requestproductitems',
                                'id' => (string)$requestProductItem->getId()
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'requestproducts'],
            $data
        );

        $result = $this->jsonToArray($response->getContent());
        $entityId = $result['data']['id'];
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());

        $this->assertInstanceOf(RequestProduct::class, $requestProduct);
        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());

        return (int)$result['data']['id'];
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
                'type' => 'requestproducts',
                'attributes' => [
                    'comment' => 'Test2'
                ]
            ]
        ];

        $this->patch(
            ['entity' => 'requestproducts', 'id' => (string)$entityId],
            $data
        );

        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        $this->assertEquals('Test2', $requestProduct->getComment());
    }

    /**
     * @depends testCreateEntity
     *
     * @param int $entityId
     */
    public function testDeleteEntity($entityId)
    {
        $this->delete(
            ['entity' => 'requestproducts', 'id' => $entityId]
        );

        $this->assertNull(
            $this->getEntityManager()->find(RequestProduct::class, $entityId)
        );
    }
}
