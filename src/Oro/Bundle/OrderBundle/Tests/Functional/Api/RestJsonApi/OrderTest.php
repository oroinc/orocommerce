<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OrderTest extends RestJsonApiTestCase
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
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('cget_order.yml', $response);
    }

    public function testGetListFilteredByIdentifier(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier]' => 'SIMPLE_order2']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber'   => 'PO2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByIdentifierNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier][neq]' => 'SIMPLE_order2']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@simple_order->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order3->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order5->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order6->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@my_order->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralIdentifiers(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier]' => 'SIMPLE_order2,SIMPLE_order3']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber'   => 'PO2'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order3->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order3',
                            'poNumber'   => 'PO3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralIdentifiersNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier][neq]' => 'SIMPLE_order2,SIMPLE_order3']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@simple_order->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order5->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order6->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@my_order->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByPoNumber(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber]' => 'po2']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber'   => 'PO2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByPoNumberNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber][neq]' => 'po2']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@simple_order->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order3->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order4->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order5->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order6->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@my_order->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralPoNumbers(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber]' => 'po2,po3']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber'   => 'PO2'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order3->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order3',
                            'poNumber'   => 'PO3'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order4->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order4',
                            'poNumber'   => 'PO3'
                        ]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order5->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order5',
                            'poNumber'   => 'PO3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredBySeveralPoNumbersNeq(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[poNumber][neq]' => 'po2,po3']);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'orders', 'id' => '<toString(@simple_order->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@simple_order6->id)>'],
                    ['type' => 'orders', 'id' => '<toString(@my_order->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByExternalTrue(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[external]' => '1']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order6->id)>',
                        'attributes' => ['identifier' => 'simple_order6', 'external' => true]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByExternalFalse(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[external]' => '0']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@my_order->id)>',
                        'attributes' => ['identifier' => 'my_order', 'external' => false]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListSortedByExternalAsc(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['sort' => 'external,id']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@my_order->id)>',
                        'attributes' => ['identifier' => 'my_order', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order6->id)>',
                        'attributes' => ['identifier' => 'simple_order6', 'external' => true]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListSortedByExternalDesc(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['sort' => '-external,id']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order6->id)>',
                        'attributes' => ['identifier' => 'simple_order6', 'external' => true]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => '<toString(@my_order->id)>',
                        'attributes' => ['identifier' => 'my_order', 'external' => false]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>']
        );

        $this->assertResponseContains('get_order.yml', $response);
    }

    public function testCreate(): void
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
        self::assertSame($this->getReference(LoadOrderUsers::ORDER_USER_1), $order->getCreatedBy());
        self::assertSame('73.5400', $order->getSubtotal());
        self::assertSame('73.5400', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
        self::assertEquals('USD', $order->getCurrency());
        self::assertNotEmpty($order->getOwner()->getId());
        self::assertEquals($organizationId, $order->getOrganization()->getId());
        // the status should be read-only when "Enable External Status Management" configuration option is disabled
        self::assertNull($order->getStatus());
        $lineItems = $order->getLineItems();
        self::assertEquals(2, $lineItems->count());

        /** @var OrderLineItem $productKitLineItem */
        $productKitLineItem = $lineItems->get(1);
        self::assertEquals(1, $productKitLineItem->getKitItemLineItems()->count());
    }

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSku(): void
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

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSkuWhenSkuIsNumber(): void
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

    public function testTryToCreateWhenLineItemDoesNotHaveProductRelationshipAndHaveUnknownProductSku(): void
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

    public function testCreateWhenLineItemDoesNotHaveProductRelationshipButHaveProductSkuForFreeFormProduct(): void
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

    public function testCreateWhenLineItemHasProductRelationshipAndProductSkuForAnotherProduct(): void
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

    public function testCreateWhenLineItemHasProductRelationshipAndProductSkuForAnotherProductForFreeFormProduct(): void
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

    public function testCreateExternal(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['external'] = true;
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'external' => true
                    ]
                ]
            ],
            $response
        );

        /** @var Order $updatedOrder */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertTrue($order->isExternal());
    }

    public function testUpdate(): void
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
                        ],
                        'status'      => [
                            'data' => [
                                'type' => 'orderstatuses',
                                'id'   => 'open'
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
        // the status should be read-only when "Enable External Status Management" configuration option is disabled
        self::assertNull($updatedOrder->getStatus());
    }

    public function testTryToUpdateCreatedBy(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'relationships' => [
                        'createdBy' => [
                            'data' => [
                                'type' => 'users',
                                'id'   => '<toString(@order.simple_user2->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'relationships' => [
                        'createdBy' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertNull($updatedOrder->getCreatedBy());
    }

    public function testTryToUpdateExternal(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'external' => true
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'external' => false
                    ]
                ]
            ],
            $response
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertFalse($updatedOrder->isExternal());
    }

    public function testAddProductKitLineItem(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_4)->getId();

        $data = $this->getRequestData('add_product_kit_line_item_to_order.yml');
        // Oro doesn't take into account order line item price from request body if product with type kit
        self::assertEquals(200, $data['included'][0]['attributes']['value']);

        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            $data
        );

        $responseContent = $this->updateResponseContent('add_product_kit_line_item_to_order.yml', $response);
        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        /** @var OrderLineItem $lineItem */
        foreach ($updatedOrder->getLineItems() as $k => $lineItem) {
            $responseContent['data']['relationships']['lineItems']['data'][$k]['id'] =
                (string)$lineItem->getId();
        }
        $this->assertResponseContains($responseContent, $response);
    }

    public function testGetSubresourceForOwner(): void
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

    public function testGetRelationshipForOwner(): void
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

    public function testGetSubresourceForOrganization(): void
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

    public function testGetRelationshipForOrganization(): void
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

    public function testGetSubresourceForCustomer(): void
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

    public function testGetRelationshipForCustomer(): void
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

    public function testGetSubresourceForCustomerUser(): void
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

    public function testGetRelationshipForCustomerUser(): void
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

    public function testGetSubresourceForProductKitLineItems(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_3);
        $productKitLineItemId = $this->getReference('product_kit_2_line_item.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$order->getId(), 'association' => 'lineItems']
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => (string)$productKitLineItemId]]],
            $response
        );
    }

    public function testGetRelationshipForProductKitLineItems(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_3);
        $productKitLineItemId = $this->getReference('product_kit_2_line_item.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$order->getId(), 'association' => 'lineItems']
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'orderlineitems', 'id' => (string)$productKitLineItemId]]],
            $response
        );
    }

    public function testGetSubresourceForStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_3)->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status']
        );

        // the status should not be returned when "Enable External Status Management" configuration option is disabled
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testGetRelationshipForStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_3)->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status']
        );

        // the status should not be returned when "Enable External Status Management" configuration option is disabled
        $this->assertResponseContains(['data' => null], $response);
    }

    public function testTryToUpdateStatusViaRelationship(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $this->patchRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status'],
            ['data' => ['type' => 'orderstatuses', 'id' => 'open']]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        // the status should be read-only when "Enable External Status Management" configuration option is disabled
        self::assertNull($updatedOrder->getStatus());
    }

    public function testTryToCreateWithoutLineItems(): void
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

    public function testTryToSetEmptyLineItems(): void
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

    public function testTryToDeleteLastLineItemFromOrder(): void
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

    public function testTryToMoveExitingLineItemToNewOrder(): void
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

    public function testTryToMoveExitingLineItemToAnotherOrder(): void
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

    public function testDeleteLineItemFromOrder(): void
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

    public function testDeleteList(): void
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
