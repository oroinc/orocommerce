<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItemTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains('cget_line_item.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testGetProductKitLineItem(): void
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@product_kit_2_line_item.1->id)>']
        );

        $this->assertResponseContains('get_product_kit_line_item.yml', $response);
    }

    public function testGetListCheckThatFilteringByCreatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [
                'filter[createdAt]' => '@order_line_item.1->createdAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@order_line_item.1->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatFilteringByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            [
                'filter[updatedAt]' => '@order_line_item.1->updatedAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@order_line_item.1->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatSortingByCreatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['sort' => '-createdAt', 'filter[order]' => '<toString(@simple_order->id)>']
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']);
    }

    public function testGetListCheckThatSortingByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orderlineitems'],
            ['sort' => '-updatedAt', 'filter[order]' => '<toString(@simple_order->id)>']
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']);
    }

    public function testCreateWithFreeFormProduct(): void
    {
        $productUnitId = $this->getReference(LoadProductUnits::BOTTLE)->getCode();
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $productSku = $this->getReference(LoadProductData::PRODUCT_1)->getSku();
        $parentProductId = $this->getReference(LoadProductData::PRODUCT_3)->getId();
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->post(
            ['entity' => 'orderlineitems'],
            'create_line_item_with_free_form_product.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        $order = $lineItem->getOrder();
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame(6.0, $lineItem->getQuantity());
        self::assertEquals($productUnitId, $lineItem->getProductUnit()->getCode());
        self::assertSame('200.0000', $lineItem->getValue());
        self::assertEquals('USD', $lineItem->getCurrency());
        self::assertEquals($productUnitId, $lineItem->getProductUnitCode());
        self::assertEquals($orderId, $lineItem->getOrder()->getId());
        self::assertEquals($productId, $lineItem->getProduct()->getId());
        self::assertEquals($parentProductId, $lineItem->getParentProduct()->getId());
        self::assertEquals(Price::create(200, 'USD'), $lineItem->getPrice());

        self::assertSame('1644.5000', $order->getSubtotal());
        self::assertSame('1644.5000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testCreateWithProductSku(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);
        $productId = $product->getId();
        $productSku = $product->getSku();

        $response = $this->post(
            ['entity' => 'orderlineitems'],
            'create_line_item_with_product_sku.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        $order = $lineItem->getOrder();
        self::assertEquals($productSku, $lineItem->getProduct()->getSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());

        self::assertSame('1644.5000', $order->getSubtotal());
        self::assertSame('1644.5000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testCreateProductKitLineItem(): void
    {
        $order = $this->getReference('simple_order3');
        self::assertCount(1, $order->getLineItems());
        self::assertSame('789.0000', $order->getSubtotal());
        self::assertSame('1234.0000', $order->getTotal());

        $data = $this->getRequestData('create_product_kit_line_item.yml');
        self::assertSame(555, $data['data']['attributes']['value']);

        $response = $this->post(
            ['entity' => 'orderlineitems'],
            'create_product_kit_line_item.yml'
        );

        $lineItemId = (int)$this->getResourceId($response);
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->getRepository(OrderLineItem::class)->find($lineItemId);
        self::assertNotNull($lineItem);
        $order = $lineItem->getOrder();
        self::assertNotNull($order);
        self::assertCount(2, $order->getLineItems());
        self::assertNotEmpty($lineItem->getChecksum());

        $responseContent = $this->getResponseData('create_product_kit_line_item.yml');
        $responseContent['data']['attributes']['checksum'] = $lineItem->getChecksum();

        $responseContent = $this->updateResponseContent($responseContent, $response);
        $this->assertResponseContains($responseContent, $response);

        self::assertSame('157.6700', $order->getSubtotal());
        self::assertSame('157.6700', $order->getTotal());
    }

    public function testTryToCreateEmptyValue(): void
    {
        $data = $this->getRequestData('create_line_item_with_product_sku.yml');
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'orderlineitems'],
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

    public function testTryToCreateEmptyCurrency(): void
    {
        $data = $this->getRequestData('create_line_item_with_product_sku.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'orderlineitems'],
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

    public function testTryToCreateWrongValue(): void
    {
        $data = $this->getRequestData('create_line_item_with_product_sku.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'orderlineitems'],
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

    public function testTryToCreateWithCreatedAtAndUpdatedAt(): void
    {
        $createdAt = (new \DateTime('now - 10 day'))->format('Y-m-d\TH:i:s\Z');
        $updatedAt = (new \DateTime('now - 9 day'))->format('Y-m-d\TH:i:s\Z');
        $data = $this->getRequestData('create_line_item_with_product_sku.yml');
        $data['data']['attributes']['createdAt'] = $createdAt;
        $data['data']['attributes']['updatedAt'] = $updatedAt;

        $response = $this->post(['entity' => 'orderlineitems'], $data);

        $lineItemId = (int)$this->getResourceId($response);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        // createdAt and updatedAt fields are read-only for order line items
        self::assertNotEquals($createdAt, $lineItem->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
        self::assertNotEquals($updatedAt, $lineItem->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
    }

    public function testUpdate(): void
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();

        $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type'       => 'orderlineitems',
                    'id'         => (string)$lineItemId,
                    'attributes' => [
                        'quantity' => 50,
                        'value'    => 100,
                        'currency' => 'EUR'
                    ]
                ]
            ]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        $order = $lineItem->getOrder();
        self::assertSame(50.0, $lineItem->getQuantity());
        self::assertSame('100.0000', $lineItem->getValue());
        self::assertEquals('EUR', $lineItem->getCurrency());
        self::assertEquals(Price::create(100, 'EUR'), $lineItem->getPrice());

        self::assertSame('5366.0000', $order->getSubtotal());
        self::assertSame('5366.0000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testTryToUpdateCreatedAtAndUpdatedAt(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $lineItemCreatedAt = $lineItem->getCreatedAt()->format('Y-m-d\TH:i:s\Z');
        $lineItemNewUpdatedAt = (new \DateTime('now - 9 day'))->format('Y-m-d\TH:i:s\Z');

        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => $lineItemId],
            [
                'data' => [
                    'type'          => 'orderlineitems',
                    'id'            => (string)$lineItemId,
                    'attributes' => [
                        'createdAt' => (new \DateTime('now - 10 day'))->format('Y-m-d\TH:i:s\Z'),
                        'updatedAt' => $lineItemNewUpdatedAt
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'orderlineitems',
                    'id'            => (string)$lineItemId,
                    'attributes' => [
                        'createdAt' => $lineItemCreatedAt
                    ]
                ]
            ],
            $response
        );

        /** @var OrderLineItem $updatedLineItem */
        $updatedLineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        // createdAt and updatedAt fields are read-only for order line items
        self::assertEquals($lineItemCreatedAt, $updatedLineItem->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
        self::assertNotEquals($lineItemNewUpdatedAt, $updatedLineItem->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
    }

    public function testGetSubresourceForOrder(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testGetRelationshipForOrder(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testTryToUpdateRelationshipForOrder(): void
    {
        $response = $this->patchRelationship(
            [
                'entity'      => 'orderlineitems',
                'id'          => '<toString(@order_line_item.1->id)>',
                'association' => 'order'
            ],
            ['data' => ['type' => 'orders', 'id' => '<toString(@my_order->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProduct(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productId = $lineItem->getProduct()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'product']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$productId]],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productId = $lineItem->getProduct()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'product']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$productId]],
            $response
        );
    }

    public function testUpdateRelationshipForProduct(): void
    {
        $lineItemId = $this->getReference('order_line_item.2')->getId();
        $productId = $this->getReference('product-1')->getId();

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'product'],
            ['data' => ['type' => 'products', 'id' => (string)$productId]]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testGetSubresourceForParentProduct(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $parentProductId = $lineItem->getParentProduct()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'parentProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$parentProductId]],
            $response
        );
    }

    public function testGetRelationshipForParentProduct(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $parentProductId = $lineItem->getParentProduct()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'parentProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => (string)$parentProductId]],
            $response
        );
    }

    public function testUpdateRelationshipForParentProduct(): void
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();
        $parentProductId = $this->getReference('product-2')->getId();

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'parentProduct'],
            ['data' => ['type' => 'products', 'id' => (string)$parentProductId]]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertSame($parentProductId, $lineItem->getParentProduct()->getId());
    }

    public function testGetSubresourceForProductUnit(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productUnitId = $lineItem->getProductUnit()->getCode();

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $productUnitId]],
            $response
        );
    }

    public function testGetRelationshipForProductUnit(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $productUnitId = $lineItem->getProductUnit()->getCode();

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => $productUnitId]],
            $response
        );
    }

    public function testUpdateRelationshipForProductUnit(): void
    {
        $lineItemId = $this->getReference('order_line_item.1')->getId();
        $productUnitCode = $this->getReference('product_unit.box')->getCode();

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'productUnit'],
            ['data' => ['type' => 'productunits', 'id' => (string)$productUnitCode]]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertSame($productUnitCode, $lineItem->getProductUnit()->getCode());
    }

    public function testGetSubresourceForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
            ];
        }

        $response = $this->getSubresource(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );

        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }

    public function testGetRelationshipForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'type' => 'orderproductkititemlineitems',
                'id' => (string)$kitItemLineItem->getId(),
            ];
        }

        $response = $this->getRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItem->getId(), 'association' => 'kitItemLineItems']
        );

        $this->assertResponseContains(['data' => $kitItemLineItemsData], $response);
    }

    public function testUpdateRelationshipForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        self::assertEquals(2, $lineItem->getKitItemLineItems()->count());

        $lineItemId = $lineItem->getId();
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();

        $this->patchRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'kitItemLineItems'],
            ['data' => [['type' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId]]]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals(1, $lineItem->getKitItemLineItems()->count());
    }

    public function testTryToAddRelationshipForKitItemLineItems(): void
    {
        $response = $this->postRelationship(
            [
                'entity' => 'orderlineitems',
                'id' => '<toString(@product_kit_2_line_item.1->id)>',
                'association' => 'kitItemLineItems',
            ],
            [
                'data' => [],
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testTryToDeleteRelationshipForKitItemLineItems(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('product_kit_2_line_item.1');
        self::assertEquals(2, $lineItem->getKitItemLineItems()->count());

        $lineItemId = $lineItem->getId();
        $kitItemLineItemId = $this->getReference('order_product_kit_2_line_item.1_kit_item_line_item.1')->getId();

        $this->deleteRelationship(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId, 'association' => 'kitItemLineItems'],
            ['data' => [['type' => 'orderproductkititemlineitems', 'id' => (string)$kitItemLineItemId]]]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertEquals(1, $lineItem->getKitItemLineItems()->count());
    }

    public function testUpdateOrderForExistingLineItemWhenOrderIdEqualsToLineItemOrderId(): void
    {
        $lineItemId = $this->getReference('order_line_item.2')->getId();
        $orderId = $this->getReference('simple_order')->getId();

        $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type'          => 'orderlineitems',
                    'id'            => (string)$lineItemId,
                    'relationships' => [
                        'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                    ]
                ]
            ]
        );

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertSame($orderId, $lineItem->getOrder()->getId());
    }

    public function testTryToSetNullOrderForExistingLineItem(): void
    {
        $lineItemId = $this->getReference('order_line_item.2')->getId();

        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type'          => 'orderlineitems',
                    'id'            => (string)$lineItemId,
                    'relationships' => [
                        'order' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/relationships/order/data']
                ],
                [
                    'title'  => 'unchangeable field constraint',
                    'detail' => 'Line Item order cannot be changed once set.',
                    'source' => ['pointer' => '/data/relationships/order/data']
                ]
            ],
            $response
        );
    }

    public function testTryToChangeOrderForExistingLineItem(): void
    {
        $lineItemId = $this->getReference('order_line_item.2')->getId();
        $orderId = $this->getReference('my_order')->getId();

        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId],
            [
                'data' => [
                    'type'          => 'orderlineitems',
                    'id'            => (string)$lineItemId,
                    'relationships' => [
                        'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'Line Item order cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/order/data']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();
        $this->delete(
            ['entity' => 'orderlineitems', 'id' => (string)$lineItemId]
        );

        self::assertNull($this->getEntityManager()->find(OrderLineItem::class, $lineItemId));
        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertSame(1, $order->getLineItems()->count());
        self::assertSame('366.0000', $order->getSubtotal());
        self::assertSame('366.0000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testTryToDeleteLastItem(): void
    {
        $response = $this->delete(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.3->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: Please add at least one Line Item.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference('order_line_item.1');
        $lineItemId = $lineItem->getId();
        $orderId = $lineItem->getOrder()->getId();
        $lineItemCount = $lineItem->getOrder()->getLineItems()->count();
        $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => (string)$lineItemId]]
        );

        self::assertNull($this->getEntityManager()->find(OrderLineItem::class, $lineItemId));
        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertSame($lineItemCount - 1, $order->getLineItems()->count());
        self::assertSame('366.0000', $order->getSubtotal());
        self::assertSame('366.0000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testTryToDeleteListForAllItems(): void
    {
        $lineItem1Id = $this->getReference('order_line_item.1')->getId();
        $lineItem2Id = $this->getReference('order_line_item.2')->getId();
        $orderId = $this->getReference('order_line_item.1')->getOrder()->getId();
        $response = $this->cdelete(
            ['entity' => 'orderlineitems'],
            ['filter' => ['id' => implode(',', [$lineItem1Id, $lineItem2Id])]],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: Please add at least one Line Item.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertSame(2, $order->getLineItems()->count());
    }
}
