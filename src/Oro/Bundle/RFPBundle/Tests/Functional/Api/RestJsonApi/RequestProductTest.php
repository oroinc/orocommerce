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
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'rfqproducts'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * LoadRequestData::NUM_LINE_ITEMS
            * LoadRequestData::NUM_PRODUCTS;

        self::assertResponseCount($expectedCount, $response);
    }

    public function testGet(): void
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestProduct::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqproducts', 'id' => $entity->getId()]
        );

        self::assertResponseNotEmpty($response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'rfqproducts'],
            'create_request_product.yml'
        );

        $entityId = (int)$this->getResourceId($response);

        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        self::assertEquals('Test', $requestProduct->getComment());

        $product = $this->getEntityManager()->find(
            Product::class,
            $this->getReference(LoadProductData::PRODUCT_1)->getId()
        );
        self::assertEquals($product->getSku(), $requestProduct->getProductSku());
    }

    public function testTryToCreateWithRequestProductItemFromAnotherRequestProduct(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $response = $this->post(
            ['entity' => 'rfqproducts'],
            [
                'data' => [
                    'type' => 'rfqproducts',
                    'attributes' => [
                        'comment' => 'Test'
                    ],
                    'relationships' => [
                        'request' => [
                            'data' => ['type' => 'rfqs', 'id' => (string)$request->getId()]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'requestProductItems' => [
                            'data' => [
                                ['type' => 'rfqproductitems', 'id' => (string)$requestProductItem->getId()]
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
                'source' => ['pointer' => '/data/relationships/requestProductItems/data/0']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        $entityId = $requestProduct->getId();

        $this->patch(
            ['entity' => 'rfqproducts', 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => 'rfqproducts',
                    'id' => (string)$entityId,
                    'attributes' => [
                        'comment' => 'Test2'
                    ]
                ]
            ]
        );

        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        self::assertEquals('Test2', $requestProduct->getComment());
    }

    public function testTryToUseRequestProductItemFromAnotherRequestProduct(): void
    {
        /** @var Request $request1 */
        $request1 = $this->getReference(LoadRequestData::REQUEST1);
        /** @var RequestProduct $requestProduct1 */
        $requestProduct1 = $request1->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem1 */
        $requestProductItem1 = $requestProduct1->getRequestProductItems()->first();
        $entityId = $requestProduct1->getId();

        /** @var Request $request2 */
        $request2 = $this->getReference(LoadRequestData::REQUEST2);
        /** @var RequestProduct $requestProduct2 */
        $requestProduct2 = $request2->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem2 */
        $requestProductItem2 = $requestProduct2->getRequestProductItems()->first();

        $response = $this->patch(
            ['entity' => 'rfqproducts', 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => 'rfqproducts',
                    'id' => (string)$entityId,
                    'relationships' => [
                        'requestProductItems' => [
                            'data' => [
                                ['type' => 'rfqproductitems', 'id' => (string)$requestProductItem1->getId()],
                                ['type' => 'rfqproductitems', 'id' => (string)$requestProductItem2->getId()]
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
                'source' => ['pointer' => '/data/relationships/requestProductItems/data/1']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        $entityId = $requestProduct->getId();

        $this->delete(
            ['entity' => 'rfqproducts', 'id' => $entityId]
        );

        $entity = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        self::assertTrue(null === $entity);
    }
}
