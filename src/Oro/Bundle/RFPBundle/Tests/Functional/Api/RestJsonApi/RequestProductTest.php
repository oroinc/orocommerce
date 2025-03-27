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

    public function testCreate(): int
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
                'type' => 'rfqproducts',
                'attributes' => [
                    'comment' => 'Test'
                ],
                'relationships' => [
                    'request' => [
                        'data' => ['type' => 'rfqs', 'id' => (string)$request->getId()]
                    ],
                    'product' => [
                        'data' => ['type' => 'products', 'id' => (string)$product->getId()]
                    ],
                    'requestProductItems' => [
                        'data' => [
                            [
                                'type' => 'rfqproductitems',
                                'id' => (string)$requestProductItem->getId()
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'rfqproducts'],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        $entityId = $result['data']['id'];
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());

        self::assertInstanceOf(RequestProduct::class, $requestProduct);
        self::assertEquals($product->getSku(), $requestProduct->getProductSku());

        return (int)$result['data']['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $entityId): void
    {
        $data = [
            'data' => [
                'id' => (string)$entityId,
                'type' => 'rfqproducts',
                'attributes' => [
                    'comment' => 'Test2'
                ]
            ]
        ];

        $this->patch(
            ['entity' => 'rfqproducts', 'id' => (string)$entityId],
            $data
        );

        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        self::assertEquals('Test2', $requestProduct->getComment());
    }

    /**
     * @depends testCreate
     */
    public function testDelete(int $entityId): void
    {
        $this->delete(
            ['entity' => 'rfqproducts', 'id' => $entityId]
        );

        $entity = $this->getEntityManager()->find(RequestProduct::class, $entityId);
        self::assertTrue(null === $entity);
    }
}
