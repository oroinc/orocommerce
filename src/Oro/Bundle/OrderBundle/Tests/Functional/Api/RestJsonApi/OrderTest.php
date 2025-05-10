<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderTest extends RestJsonApiTestCase
{
    use MessageQueueAssertTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);

        $this->getOptionalListenerManager()
            ->enableListener('oro_order.order.listener.orm.order_shipping_status_listener');
    }

    private function generateLineItemChecksum(OrderLineItem $lineItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($lineItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the line item checksum.');

        return $checksum;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('cget_order.yml', $response);
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseData);
    }

    public function testGetListFilteredByIdentifier(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['filter[identifier]' => 'SIMPLE_order2']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber' => 'PO2'
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber' => 'PO2'
                        ]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order3->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order3',
                            'poNumber' => 'PO3'
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber' => 'PO2'
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order2',
                            'poNumber' => 'PO2'
                        ]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order3->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order3',
                            'poNumber' => 'PO3'
                        ]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order4->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order4',
                            'poNumber' => 'PO3'
                        ]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order5->id)>',
                        'attributes' => [
                            'identifier' => 'simple_order5',
                            'poNumber' => 'PO3'
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order6->id)>',
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@my_order->id)>',
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@my_order->id)>',
                        'attributes' => ['identifier' => 'my_order', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order6->id)>',
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
                        'type' => 'orders',
                        'id' => '<toString(@simple_order6->id)>',
                        'attributes' => ['identifier' => 'simple_order6', 'external' => true]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order->id)>',
                        'attributes' => ['identifier' => 'simple_order', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order2->id)>',
                        'attributes' => ['identifier' => 'simple_order2', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order3->id)>',
                        'attributes' => ['identifier' => 'simple_order3', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order4->id)>',
                        'attributes' => ['identifier' => 'simple_order4', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@simple_order5->id)>',
                        'attributes' => ['identifier' => 'simple_order5', 'external' => false]
                    ],
                    [
                        'type' => 'orders',
                        'id' => '<toString(@my_order->id)>',
                        'attributes' => ['identifier' => 'my_order', 'external' => false]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListCheckThatFilteringByCreatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orders'],
            [
                'filter[createdAt]' => '@simple_order->createdAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@simple_order->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@simple_order->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatFilteringByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(
            ['entity' => 'orders'],
            [
                'filter[updatedAt]' => '@simple_order->updatedAt->format("Y-m-d\TH:i:s\Z")',
                'filter[id]' => '<toString(@simple_order->id)>'
            ]
        );
        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => '<toString(@simple_order->id)>']]],
            $response
        );
    }

    public function testGetListCheckThatSortingByCreatedAtIsSupported(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['sort' => '-createdAt']);
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(7, $responseData['data']);
    }

    public function testGetListCheckThatSortingByUpdatedAtIsSupported(): void
    {
        $response = $this->cget(['entity' => 'orders'], ['sort' => '-updatedAt']);
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(7, $responseData['data']);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>']
        );

        $this->assertResponseContains('get_order.yml', $response);
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseData);
    }

    public function testGetIncludeOrderLineItems(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>'],
            ['include' => 'lineItems']
        );

        $this->assertResponseContains('get_order_include_order_line_items.yml', $response);
    }

    public function testGetIncludeOrderSubtotals(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>'],
            ['include' => 'orderSubtotals']
        );

        $this->assertResponseContains('get_order_include_order_subtotals.yml', $response);
    }

    public function testGetIncludeOrderSubtotalsAndWithFieldsFilters(): void
    {
        $response = $this->get(
            ['entity' => 'orders', 'id' => '<toString(@simple_order->id)>'],
            [
                'include' => 'orderSubtotals',
                'fields[orders]' => 'poNumber,customerNotes,orderSubtotals',
                'fields[ordersubtotals]' => 'label,amount,currency,data'
            ]
        );

        $this->assertResponseContains('get_order_partially_include_order_subtotals.yml', $response);
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']['attributes'], 'attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'relationships');
        foreach ($responseData['included'] as $i => $item) {
            self::assertCount(4, $item['attributes'], sprintf('included.%d.attributes', $i));
            self::assertArrayNotHasKey('relationships', $item, sprintf('included.%d.relationships', $i));
        }
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $ownerId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();

        $response = $this->post(
            ['entity' => 'orders'],
            'create_order_min.yml'
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals((string)$orderId, $order->getIdentifier());
        self::assertNull($order->getPoNumber());
        self::assertNull($order->getCreatedBy());
        self::assertSame('10.0000', $order->getSubtotal());
        self::assertSame('10.0000', $order->getTotal());
        self::assertNull($order->getTotalDiscounts());
        self::assertEquals('USD', $order->getCurrency());
        self::assertEquals($ownerId, $order->getOwner()->getId());
        self::assertEquals($organizationId, $order->getOrganization()->getId());
        // the status should be read-only when "Enable External Status Management" configuration option is disabled
        self::assertNull($order->getStatus());
        self::assertEquals('open', $order->getInternalStatus()->getInternalId());
        self::assertEquals('not_shipped', $order->getShippingStatus()->getInternalId());
        self::assertEquals(1, $order->getLineItems()->count());
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($this->generateLineItemChecksum($lineItem), $lineItem->getChecksum());
    }

    public function testCreate(): void
    {
        $organizationId = $this->getReference('organization')->getId();

        $response = $this->post(
            ['entity' => 'orders'],
            'create_order.yml'
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('new_order', $order->getIdentifier());
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
        self::assertEquals('open', $order->getInternalStatus()->getInternalId());
        self::assertEquals('not_shipped', $order->getShippingStatus()->getInternalId());
        $lineItems = $order->getLineItems();
        self::assertEquals(2, $lineItems->count());
        $lineItem = $order->getLineItems()->first();
        self::assertEquals($this->generateLineItemChecksum($lineItem), $lineItem->getChecksum());

        /** @var OrderLineItem $productKitLineItem */
        $productKitLineItem = $lineItems->get(1);
        self::assertEquals(1, $productKitLineItem->getKitItemLineItems()->count());
        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $productKitLineItem->getKitItemLineItems()->first();
        self::assertSame(10.59, $kitItemLineItem->getValue());
    }

    public function testTryToCreateWithoutValueForHitItemLineItem(): void
    {
        $data = $this->getRequestData('create_order.yml');
        unset($data['included'][2]['attributes']['value']);

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'Price value should not be blank. Source: price.',
                    'source' => ['pointer' => '/included/2']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'Price value should not be blank.',
                    'source' => ['pointer' => '/included/2/attributes/value']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithCreatedAtAndUpdatedAt(): void
    {
        $createdAt = (new \DateTime('now - 10 day'))->format('Y-m-d\TH:i:s\Z');
        $updatedAt = (new \DateTime('now - 9 day'))->format('Y-m-d\TH:i:s\Z');
        $data = $this->getRequestData('create_order.yml');
        $data['data']['attributes']['createdAt'] = $createdAt;
        $data['data']['attributes']['updatedAt'] = $updatedAt;

        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        // createdAt and updatedAt fields are read-only for orders
        self::assertNotEquals($createdAt, $order->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
        self::assertNotEquals($updatedAt, $order->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
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

        /** @var Order $order */
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

        /** @var Order $order */
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
                'title' => 'line item product constraint',
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

        /** @var Order $order */
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

        /** @var Order $order */
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
            'id' => (string)$productId
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
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
            'id' => (string)$productId
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();
        self::assertEquals('Test', $lineItem->getFreeFormProduct());
        self::assertEquals($productSku, $lineItem->getProductSku());
        self::assertSame($productId, $lineItem->getProduct()->getId());
    }

    public function testTryToCreateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['relationships']['customer']['data']['id'] = '<toString(@customer.level_1_1->id)>';

        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
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
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'external' => true
                    ]
                ]
            ],
            $response
        );

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertTrue($order->isExternal());
    }

    public function testCreateWithShippingStatus(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['relationships']['shippingStatus']['data'] = [
            'type' => 'ordershippingstatuses',
            'id' => 'shipped'
        ];
        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'relationships' => [
                        'shippingStatus' => [
                            'data' => ['type' => 'ordershippingstatuses', 'id' => 'shipped']
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('shipped', $order->getShippingStatus()->getInternalId());
    }

    public function testCreateWithLineItemReadonlyChecksum(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        $data['included'][0]['attributes']['checksum'] = '123456789';
        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);
        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertCount(1, $order->getLineItems());
        $orderLineItem = $order->getLineItems()->first();
        $expectedChecksum = $this->generateLineItemChecksum($orderLineItem);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$orderId;
        $expectedData['data']['relationships']['lineItems']['data'][0]['id'] = (string)$orderLineItem->getId();
        $expectedData['included'][0]['id'] = (string)$orderLineItem->getId();
        $expectedData['included'][0]['attributes']['value'] = '10.0000';
        $expectedData['included'][0]['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $orderLineItem->getChecksum());
    }

    public function testCreateWithMinimumOrderAmountNotMet(): void
    {
        $data = $this->getRequestData('create_order.yml');

        $minimumOrderAmountConfigKey = 'oro_checkout.minimum_order_amount';
        $configManager = $this->getConfigManager();
        $originalMinimumOrderAmount = $configManager->get($minimumOrderAmountConfigKey);
        $configManager->set($minimumOrderAmountConfigKey, [['value' => '112.55', 'currency' => 'USD']]);
        $configManager->flush();

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('2345678', $order->getPoNumber());
        self::assertSame('73.5400', $order->getSubtotal());
        self::assertSame('73.5400', $order->getTotal());

        $configManager->set($minimumOrderAmountConfigKey, $originalMinimumOrderAmount);
        $configManager->flush();
    }

    public function testCreateWithMaximumOrderAmountNotMet(): void
    {
        $data = $this->getRequestData('create_order.yml');

        $maximumOrderAmountConfigKey = 'oro_checkout.maximum_order_amount';
        $configManager = $this->getConfigManager();
        $originalMaximumOrderAmount = $configManager->get($maximumOrderAmountConfigKey);
        $configManager->set($maximumOrderAmountConfigKey, [['value' => '70.00', 'currency' => 'USD']]);
        $configManager->flush();

        $response = $this->post(
            ['entity' => 'orders'],
            $data
        );

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('2345678', $order->getPoNumber());
        self::assertSame('73.5400', $order->getSubtotal());
        self::assertSame('73.5400', $order->getTotal());

        $configManager->set($maximumOrderAmountConfigKey, $originalMaximumOrderAmount);
        $configManager->flush();
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
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ],
                        'status' => [
                            'data' => [
                                'type' => 'orderstatuses',
                                'id' => 'open'
                            ]
                        ],
                        'shippingStatus' => [
                            'data' => [
                                'type' => 'ordershippingstatuses',
                                'id' => 'shipped'
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
        self::assertEquals('shipped', $updatedOrder->getShippingStatus()->getInternalId());
    }

    public function testTryToUpdateCreatedAtAndUpdatedAt(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $orderCreatedAt = $order->getCreatedAt()->format('Y-m-d\TH:i:s\Z');
        $orderNewUpdatedAt = (new \DateTime('now - 9 day'))->format('Y-m-d\TH:i:s\Z');

        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'attributes' => [
                        'createdAt' => (new \DateTime('now - 10 day'))->format('Y-m-d\TH:i:s\Z'),
                        'updatedAt' => $orderNewUpdatedAt
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'attributes' => [
                        'createdAt' => $orderCreatedAt
                    ]
                ]
            ],
            $response
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        // createdAt and updatedAt fields are read-only for orders
        self::assertEquals($orderCreatedAt, $updatedOrder->getCreatedAt()->format('Y-m-d\TH:i:s\Z'));
        self::assertNotEquals($orderNewUpdatedAt, $updatedOrder->getUpdatedAt()->format('Y-m-d\TH:i:s\Z'));
    }

    public function testTryToUpdateWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();
        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'relationships' => [
                        'customer' => [
                            'data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1_1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
    }

    public function testTryToUpdateCreatedBy(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'relationships' => [
                        'createdBy' => [
                            'data' => [
                                'type' => 'users',
                                'id' => '<toString(@order.simple_user2->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
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
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'external' => true
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
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

    public function testTryToUpdateClosedOrder(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_5)->getId();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access by "EDIT" permission to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testAddProductKitLineItem(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_4)->getId();

        $data = $this->getRequestData('add_product_kit_line_item_to_order.yml');
        // Oro doesn't take into account order line item price from request body if product with type kit
        self::assertEquals(200, $data['included'][0]['attributes']['value']);

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            $data
        );

        $responseData = $this->updateResponseContent('add_product_kit_line_item_to_order.yml', $response);
        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        /** @var OrderLineItem $lineItem */
        foreach ($updatedOrder->getLineItems() as $k => $lineItem) {
            $responseData['data']['relationships']['lineItems']['data'][$k]['id'] = (string)$lineItem->getId();
        }
        $this->assertResponseContains($responseData, $response);
    }

    public function testTryToUpdateLineItemReadonlyChecksum(): void
    {
        $orderLineItemId = $this->getReference('order_line_item.3')->getId();

        $data = [
            'data' => [
                'type' => 'orderlineitems',
                'id' => (string)$orderLineItemId,
                'attributes' => [
                    'checksum' => '123456789'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'orderlineitems', 'id' => (string)$orderLineItemId],
            $data
        );

        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $this->getEntityManager()->find(OrderLineItem::class, $orderLineItemId);
        $expectedChecksum = $this->generateLineItemChecksum($orderLineItem);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $orderLineItem->getChecksum());
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

    public function testGetSubresourceForShippingStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'shippingStatus']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'ordershippingstatuses', 'id' => 'not_shipped']],
            $response
        );
    }

    public function testGetRelationshipForShippingStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'shippingStatus']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'ordershippingstatuses', 'id' => 'not_shipped']],
            $response
        );
    }

    public function testUpdateShippingStatusViaRelationship(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $this->patchRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'shippingStatus'],
            ['data' => ['type' => 'ordershippingstatuses', 'id' => 'shipped']]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('shipped', $updatedOrder->getShippingStatus()->getInternalId());
    }

    public function testUpdateShippingStatusViaRelationshipForClosedOrder(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_5)->getId();

        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'shippingStatus'],
            ['data' => ['type' => 'ordershippingstatuses', 'id' => 'not_shipped']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access by "EDIT" permission to the parent entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToCreateWithoutCurrency(): void
    {
        $data = $this->getRequestData('create_order_min.yml');
        unset($data['data']['attributes']);
        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutLineItems(): void
    {
        $data = [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'currency' => 'USD'
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
                'title' => 'count constraint',
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
                'type' => 'orders',
                'id' => '<toString(@simple_order2->id)>',
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
                'title' => 'count constraint',
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
                'title' => 'count constraint',
                'detail' => 'Please add at least one Line Item'
            ],
            $response
        );
    }

    public function testTryToMoveExitingLineItemToNewOrder(): void
    {
        $data = [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'currency' => 'USD'
                ],
                'relationships' => [
                    'customer' => [
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
                'title' => 'unchangeable field constraint',
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
                'type' => 'orders',
                'id' => '<toString(@my_order->id)>',
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
                'title' => 'unchangeable field constraint',
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
        /** @var Order $order */
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
            ['filter' => ['id' => (string)$orderId]]
        );

        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertTrue(null === $order);
    }

    public function testCreateWhenValidateEqualsToTrue(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['meta']['validate'] = true;

        $response = $this->post(['entity' => 'orders'], $data);
        $responseData = self::jsonToArray($response->getContent());
        self::assertEquals('new_order', $responseData['data']['attributes']['identifier']);
        self::assertEquals('2345678', $responseData['data']['attributes']['poNumber']);
        self::assertAllMessagesSent([]);

        $orderId = (int)$this->getResourceId($response);
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertTrue(null === $order);
    }

    public function testCreateWhenValidateEqualsToFalse(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['meta']['validate'] = false;

        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);

        /** @var Order $order */
        $order = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertNotNull($order);
        self::assertEquals('new_order', $order->getIdentifier());
        self::assertEquals('2345678', $order->getPoNumber());
        self::assertAllMessagesSent([
            [
                'topic' => IndexEntitiesByIdTopic::getName(),
                'message' => [
                    'class' => Order::class,
                    'entityIds' => [$order->getId() => $order->getId()]
                ]
            ]
        ]);
    }

    public function testCreateWithIncludeFilterWhenValidateEqualsToTrue(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['meta']['validate'] = true;
        $data['filters'] = 'include=orderSubtotals';

        $response = $this->post(['entity' => 'orders'], $data);
        self::assertAllMessagesSent([]);

        $orderId = (int)$this->getResourceId($response);
        self::assertNull($this->getEntityManager()->find(Order::class, $orderId));

        $responseData = $this->getResponseData('create_order_with_included_order_subtotals.yml');
        foreach ($responseData['data']['relationships']['orderSubtotals']['data'] as &$item) {
            $item['id'] = sprintf('%s-%s', $orderId, $item['id']);
        }
        unset($item);
        foreach ($responseData['included'] as &$item) {
            $item['id'] = sprintf('%s-%s', $orderId, $item['id']);
            $item['relationships']['order']['data']['id'] = (string)$orderId;
        }
        unset($item);
        $this->assertResponseContains($responseData, $response);
    }

    public function testCreateWithIncludeAndFieldsFiltersWhenValidateEqualsToTrue(): void
    {
        $data = $this->getRequestData('create_order.yml');
        $data['data']['meta']['validate'] = true;
        $data['filters'] = 'include=orderSubtotals'
            . '&fields[orders]=poNumber,customerNotes,orderSubtotals'
            . '&fields[ordersubtotals]=label,amount,currency,data';

        $response = $this->post(['entity' => 'orders'], $data);
        self::assertAllMessagesSent([]);

        $orderId = (int)$this->getResourceId($response);
        self::assertNull($this->getEntityManager()->find(Order::class, $orderId));

        $responseData = $this->getResponseData('create_order_with_partially_included_order_subtotals.yml');
        foreach ($responseData['data']['relationships']['orderSubtotals']['data'] as &$item) {
            $item['id'] = sprintf('%s-%s', $orderId, $item['id']);
        }
        unset($item);
        foreach ($responseData['included'] as &$item) {
            $item['id'] = sprintf('%s-%s', $orderId, $item['id']);
        }
        unset($item);
        $this->assertResponseContains($responseData, $response);

        self::assertCount(2, $responseData['data']['attributes'], 'attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'relationships');
        foreach ($responseData['included'] as $i => $item) {
            self::assertCount(4, $item['attributes'], sprintf('included.%d.attributes', $i));
            self::assertArrayNotHasKey('relationships', $item, sprintf('included.%d.relationships', $i));
        }
    }

    public function testUpdateWhenValidateEqualsToTrue(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $order->setSubtotal(11);
        $order->setTotal(10);
        $order->setTotalDiscounts(Price::create(1, $order->getCurrency()));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'meta' => ['validate' => true],
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ],
                        'status' => [
                            'data' => [
                                'type' => 'orderstatuses',
                                'id' => 'open'
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertNull($updatedOrder->getCustomerNotes());
        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        self::assertEquals('net 10', $paymentTermProvider->getObjectPaymentTerm($updatedOrder)->getLabel());
        self::assertSame('11.0000', $updatedOrder->getSubtotal());
        self::assertSame('10.0000', $updatedOrder->getTotal());
        self::assertEquals(Price::create('1.0000', 'USD'), $updatedOrder->getTotalDiscounts());
        self::assertAllMessagesSent([]);

        $responseData = self::jsonToArray($response->getContent());
        self::assertSame('test notes', $responseData['data']['attributes']['customerNotes']);
        self::assertArrayNotHasKey('included', $responseData);
    }

    public function testUpdateWhenValidateEqualsToFalse(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $order->setSubtotal(11);
        $order->setTotal(10);
        $order->setTotalDiscounts(Price::create(1, $order->getCurrency()));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'data' => [
                    'meta' => ['validate' => false],
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ],
                        'status' => [
                            'data' => [
                                'type' => 'orderstatuses',
                                'id' => 'open'
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
        self::assertNull($updatedOrder->getStatus());
        self::assertAllMessagesSent([]);

        $responseData = self::jsonToArray($response->getContent());
        self::assertSame('test notes', $responseData['data']['attributes']['customerNotes']);
    }

    public function testUpdateWithIncludeFilterWhenValidateEqualsToTrue(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $order->setSubtotal(11);
        $order->setTotal(10);
        $order->setTotalDiscounts(Price::create(1, $order->getCurrency()));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'filters' => 'include=orderSubtotals',
                'data' => [
                    'meta' => ['validate' => true],
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertNull($updatedOrder->getCustomerNotes());
        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        self::assertEquals('net 10', $paymentTermProvider->getObjectPaymentTerm($updatedOrder)->getLabel());
        self::assertSame('11.0000', $updatedOrder->getSubtotal());
        self::assertSame('10.0000', $updatedOrder->getTotal());
        self::assertEquals(Price::create('1.0000', 'USD'), $updatedOrder->getTotalDiscounts());
        self::assertAllMessagesSent([]);

        $this->assertResponseContains('update_order_with_included_order_subtotals.yml', $response);
    }

    public function testUpdateWithIncludeAndFieldsFiltersWhenValidateEqualsToTrue(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $order->setSubtotal(11);
        $order->setTotal(10);
        $order->setTotalDiscounts(Price::create(1, $order->getCurrency()));
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $response = $this->patch(
            ['entity' => 'orders', 'id' => (string)$orderId],
            [
                'filters' => 'include=orderSubtotals'
                    . '&fields[orders]=poNumber,customerNotes,orderSubtotals'
                    . '&fields[ordersubtotals]=label,amount,currency,data',
                'data' => [
                    'meta' => ['validate' => true],
                    'type' => 'orders',
                    'id' => (string)$orderId,
                    'attributes' => [
                        'customerNotes' => 'test notes'
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertNull($updatedOrder->getCustomerNotes());
        $paymentTermProvider = self::getContainer()->get('oro_payment_term.provider.payment_term');
        self::assertEquals('net 10', $paymentTermProvider->getObjectPaymentTerm($updatedOrder)->getLabel());
        self::assertSame('11.0000', $updatedOrder->getSubtotal());
        self::assertSame('10.0000', $updatedOrder->getTotal());
        self::assertEquals(Price::create('1.0000', 'USD'), $updatedOrder->getTotalDiscounts());
        self::assertAllMessagesSent([]);

        $this->assertResponseContains('update_order_with_partially_included_order_subtotals.yml', $response);

        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data']['attributes'], 'attributes');
        self::assertCount(1, $responseData['data']['relationships'], 'relationships');
        foreach ($responseData['included'] as $i => $item) {
            self::assertCount(4, $item['attributes'], sprintf('included.%d.attributes', $i));
            self::assertArrayNotHasKey('relationships', $item, sprintf('included.%d.relationships', $i));
        }
    }

    public function testTryToSetCustomerViaRelationshipWhenCustomerUserDoesNotBelongsToCustomer(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();
        $response = $this->patchRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'customer'],
            [
                'data' => [
                    'type' => 'customers',
                    'id'   => '<toString(@customer.level_1_1->id)>'
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'customer owner constraint',
                'detail' => 'The customer user does not belong to the customer.'
            ],
            $response
        );
    }
}
