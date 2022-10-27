<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderTest extends RestJsonApiTestCase
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
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('cget_order.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>']
        );

        $this->assertResponseContains('get_order.yml', $response);
    }

    public function testCreate()
    {
        $organizationId = $this->getReference(LoadOrders::ORDER_1)->getOrganization()->getId();

        $response = $this->post(
            ['entity' => 'orders'],
            'create_order.yml'
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('2345678', $order->getPoNumber());
        self::assertSame('10.0000', $order->getSubtotal());
        self::assertSame('10.0000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
        self::assertEquals('USD', $order->getCurrency());
        self::assertNotEmpty($order->getOwner()->getId());
        self::assertEquals($organizationId, $order->getOrganization()->getId());
    }

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSku()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');
        $productId = $product->getId();
        $productSku = $product->getSku();

        $data = $this->getRequestData('create_order_product_sku.yml');
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSkuWhenSkuIsNumber()
    {
        /** @var Product $product */
        $product = $this->getReference('product-100');
        $productId = $product->getId();
        $productSku = $product->getSku();

        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['productSku'] = $productSku;
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testTryToCreateWhenLineItemDoesNotHaveProductRelationshipAndHaveUnknownProductSku()
    {
        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['productSku'] = 'unknown';
        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'line item product constraint',
                'detail' => 'Please choose Product.',
                'source' => ['pointer' => '/included/0/relationships/product/data']
            ],
            $response
        );
    }

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSkuForFreeFormProduct()
    {
        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'Test';
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals('Test', $lineItem->getFreeFormProduct());
        self::assertEquals('product-1', $lineItem->getProductSku());
        self::assertTrue(null === $lineItem->getProduct());
    }

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipAndHaveUnknownProductSkuForFreeFormProduct()
    {
        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'Test';
        $data['included'][0]['attributes']['productSku'] = 'unknown';
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals('Test', $lineItem->getFreeFormProduct());
        self::assertEquals('unknown', $lineItem->getProductSku());
        self::assertTrue(null === $lineItem->getProduct());
    }

    public function testCreateWhenLineItemHasProductRelationshipAndProductSkuForAnotherProduct()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');
        $productId = $product->getId();
        $productSku = $product->getSku();

        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['productSku'] = 'product-2';
        $data['included'][0]['relationships']['product']['data'] = [
            'type' => 'products',
            'id'   => (string)$productId
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testCreateWhenLineItemHasProductRelationshipAndProductSkuForAnotherProductForFreeFormProduct()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');
        $productId = $product->getId();
        $productSku = $product->getSku();

        $data = $this->getRequestData('create_order_product_sku.yml');
        $data['included'][0]['attributes']['freeFormProduct'] = 'Test';
        $data['included'][0]['attributes']['productSku'] = 'product-2';
        $data['included'][0]['relationships']['product']['data'] = [
            'type' => 'products',
            'id'   => (string)$productId
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals('Test', $lineItem->getFreeFormProduct());
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testUpdate()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $order->setSubtotal(11);
        $order->setTotal(10);
        $order->setTotalDiscounts(Price::create(1, $order->getCurrency()));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'attributes'    => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id'   => '<toString(@payment_term.net_20->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('test notes', $updatedOrder->getCustomerNotes());
        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        self::assertEquals('net 20', $paymentTermProvider->getObjectPaymentTerm($updatedOrder)->getLabel());
        self::assertSame('444.5000', $updatedOrder->getSubtotal());
        self::assertSame('444.5000', $updatedOrder->getTotal());
        self::assertNull($updatedOrder->getTotalDiscounts());
    }

    public function testGetSubresourceForOwner()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $ownerId = $order->getOwner()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'owner']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'users', 'id' => (string)$ownerId]],
            $response
        );
    }

    public function testGetRelationshipForOwner()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $ownerId = $order->getOwner()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'owner']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'users', 'id' => (string)$ownerId]],
            $response
        );
    }

    public function testGetSubresourceForOrganization()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $organizationId = $order->getOrganization()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => (string)$organizationId]],
            $response
        );
    }

    public function testGetRelationshipForOrganization()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $organizationId = $order->getOrganization()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'organization']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => (string)$organizationId]],
            $response
        );
    }

    public function testGetSubresourceForCustomer()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $customerId = $order->getCustomer()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'customer']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => (string)$customerId]],
            $response
        );
    }

    public function testGetRelationshipForCustomer()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $customerId = $order->getCustomer()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'customer']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => (string)$customerId]],
            $response
        );
    }

    public function testGetSubresourceForCustomerUser()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $customerUserId = $order->getCustomerUser()->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'customerUser']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => (string)$customerUserId]],
            $response
        );
    }

    public function testGetRelationshipForCustomerUser()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $customerUserId = $order->getCustomerUser()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'customerUser']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => (string)$customerUserId]],
            $response
        );
    }

    public function testTryToCreateWithoutLineItems()
    {
        $data = [
            'data' => [
                'type'          => 'orders',
                'attributes'    => [
                    'identifier' => 'new_order1'
                ],
                'relationships' => [
                    'customer' => [
                        'data' => ['type' => 'customers', 'id' => '<toString(@my_order->customer->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'Please add at least one Line Item',
                'source' => ['pointer' => '/data/relationships/lineItems/data']
            ],
            $response
        );
    }

    public function testTryToSetEmptyLineItems()
    {
        $data = [
            'data' => [
                'type'          => 'orders',
                'id'            => '<toString(@simple_order2->id)>',
                'relationships' => [
                    'lineItems' => [
                        'data' => []
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '<toString(@simple_order2->id)>'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'Please add at least one Line Item',
                'source' => ['pointer' => '/data/relationships/lineItems/data']
            ],
            $response
        );
    }

    public function testTryToDeleteLastLineItemFromOrder()
    {
        $data = [
            'data' => [
                ['type' => 'orderlineitems', 'id' => '<toString(@order_line_item.3->id)>']
            ]
        ];
        $response = $this->deleteRelationship(
            ['entity' => 'orders', 'id' => '<toString(@simple_order2->id)>', 'association' => 'lineItems'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'Please add at least one Line Item'
            ],
            $response
        );
    }

    public function testTryToMoveExitingLineItemToNewOrder()
    {
        $data = [
            'data' => [
                'type'          => 'orders',
                'attributes'    => [
                    'identifier' => 'new_order1'
                ],
                'relationships' => [
                    'customer'  => [
                        'data' => ['type' => 'customers', 'id' => '<toString(@my_order->customer->id)>']
                    ],
                    'lineItems' => [
                        'data' => [
                            ['type' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'Line Item order cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/lineItems/data/0']
            ],
            $response
        );
    }

    public function testTryToMoveExitingLineItemToAnotherOrder()
    {
        $data = [
            'data' => [
                'type'          => 'orders',
                'id'            => '<toString(@my_order->id)>',
                'relationships' => [
                    'lineItems' => [
                        'data' => [
                            ['type' => 'orderlineitems', 'id' => '<toString(@order_line_item.1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'orders', 'id' => '<toString(@my_order->id)>'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'Line Item order cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/lineItems/data/0']
            ],
            $response
        );
    }

    public function testDeleteLineItemFromOrder()
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();
        $lineItemId = $this->getReference('order_line_item.2')->getId();

        $data = [
            'data' => [
                ['type' => 'orderlineitems', 'id' => (string)$lineItemId]
            ]
        ];
        $this->deleteRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'lineItems'],
            $data
        );

        $lineItem = $this->getEntityManager()->find(OrderLineItem::class, $lineItemId);
        self::assertTrue(null === $lineItem);
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        self::assertSame('78.5000', $order->getSubtotal());
        self::assertSame('78.5000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
    }

    public function testDeleteList()
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => $orderId]]
        );

        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertTrue(null === $order);
    }
}
