<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

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
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderlineitems']);

        $this->assertResponseContains('cget_line_item.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
        );

        $this->assertResponseContains('get_line_item.yml', $response);
    }

    public function testCreateWithFreeFormProduct()
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

    public function testCreateWithProductSku()
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

    public function testTryToCreateEmptyValue()
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

    public function testTryToCreateEmptyCurrency()
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

    public function testTryToCreateWrongValue()
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

    public function testUpdate()
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

    public function testGetSubresourceForOrder()
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

    public function testGetRelationshipForOrder()
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

    public function testTryToUpdateRelationshipForOrder()
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

    public function testGetSubresourceForProduct()
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

    public function testGetRelationshipForProduct()
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

    public function testUpdateRelationshipForProduct()
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

    public function testGetSubresourceForParentProduct()
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

    public function testGetRelationshipForParentProduct()
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

    public function testUpdateRelationshipForParentProduct()
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

    public function testGetSubresourceForProductUnit()
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

    public function testGetRelationshipForProductUnit()
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

    public function testUpdateRelationshipForProductUnit()
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

    public function testUpdateOrderForExistingLineItemWhenOrderIdEqualsToLineItemOrderId()
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

    public function testTryToSetNullOrderForExistingLineItem()
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

    public function testTryToChangeOrderForExistingLineItem()
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

    public function testDelete()
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

    public function testTryToDeleteLastItem()
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

    public function testDeleteList()
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

    public function testTryToDeleteListForAllItems()
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
