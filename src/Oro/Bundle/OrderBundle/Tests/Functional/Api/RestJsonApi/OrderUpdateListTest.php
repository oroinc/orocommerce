<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @dbIsolationPerTest
 */
class OrderUpdateListTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(
            [
                '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml',
                LoadCountryData::class,
                LoadRegionData::class,
            ]
        );
    }

    public function testCreateEntities()
    {
        $this->processUpdateList(
            Order::class,
            'update_list_create_orders.yml'
        );

        $response = $this->cget(
            ['entity' => 'orders'],
            ['filter[id][gt]' => '@my_order->id']
        );

        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => 'orders',
                        'id'         => 'new',
                        'attributes' => [
                            'identifier'    => 'new_order 1',
                            'poNumber'      => '2345678',
                            'shipUntil'     => '2017-04-12',
                            'currency'      => 'USD',
                            'subtotalValue' => '20.0000',
                            'totalValue'    => '20.0000',
                        ],
                    ],
                    [
                        'type'       => 'orders',
                        'id'         => 'new',
                        'attributes' => [
                            'identifier'    => 'new_order 2',
                            'poNumber'      => '2345679',
                            'shipUntil'     => '2017-04-12',
                            'currency'      => 'USD',
                            'subtotalValue' => '90.0000',
                            'totalValue'    => '90.0000',
                        ],
                    ],
                ],
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $repository = $this->getEntityManager()->getRepository(Order::class);

        self::assertEquals(2, $repository->findOneBy(['poNumber' => '2345678'])->getLineItems()->count());

        $order2LineItems = $repository->findOneBy(['poNumber' => '2345679'])->getLineItems();
        self::assertEquals(1, $order2LineItems->count());
        /** @var LineItem $lineItem */
        $lineItem = $order2LineItems->first();
        self::assertEquals($this->getReference('product-1'), $lineItem->getProduct());
        self::assertEquals('product-1', $lineItem->getProductSku());
        self::assertEquals($this->getReference('product_unit.bottle'), $lineItem->getProductUnit());
        self::assertEquals('bottle', $lineItem->getProductUnitCode());
        self::assertEquals('30.0000', $lineItem->getValue());
        self::assertEquals('USD', $lineItem->getCurrency());
        self::assertEquals(3.0, $lineItem->getQuantity());
        self::assertEquals('30.0000', $lineItem->getPrice()->getValue());
        self::assertEquals('USD', $lineItem->getPrice()->getCurrency());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateOrdersWithNewShippingTrackingsInfo()
    {
        $this->processUpdateList(
            Order::class,
            [
                'data'     => [
                    [
                        'meta'          => ['update' => true],
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order->id)>',
                        'attributes'    => ['poNumber' => '001'],
                        'relationships' => [
                            'shippingTrackings' => [
                                'data' => [
                                    [
                                        'type' => 'ordershippingtrackings',
                                        'id'   => 'tracking1'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'meta'          => ['update' => true],
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order2->id)>',
                        'attributes'    => ['poNumber' => '002'],
                        'relationships' => [
                            'shippingTrackings' => [
                                'data' => [
                                    [
                                        'type' => 'ordershippingtrackings',
                                        'id'   => 'tracking2'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'ordershippingtrackings',
                        'id'         => 'tracking1',
                        'attributes' => [
                            'method' => 'method 1',
                            'number' => 'number 1'
                        ]
                    ],
                    [
                        'type'       => 'ordershippingtrackings',
                        'id'         => 'tracking2',
                        'attributes' => [
                            'method' => 'method 3',
                            'number' => 'number 3'
                        ]
                    ]
                ]
            ]
        );

        $response = $this->cget(
            ['entity' => 'orders'],
            ['page[size]' => 2]
        );
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order->id)>',
                        'attributes'    => ['poNumber' => '001'],
                        'relationships' => [
                            'shippingTrackings' => [
                                'data' => [
                                    [
                                        'type' => 'ordershippingtrackings',
                                        'id'   => 'new'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order2->id)>',
                        'attributes'    => ['poNumber' => '002'],
                        'relationships' => [
                            'shippingTrackings' => [
                                'data' => [
                                    [
                                        'type' => 'ordershippingtrackings',
                                        'id'   => 'new'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $repository = $this->getEntityManager()->getRepository(Order::class);

        $order1Trackings = $repository->findOneBy(['poNumber' => '001'])->getShippingTrackings();
        self::assertEquals(1, $order1Trackings->count());
        $order1Tracking = $order1Trackings->first();
        self::assertEquals('method 1', $order1Tracking->getMethod());
        self::assertEquals('number 1', $order1Tracking->getNumber());

        $order2Trackings = $repository->findOneBy(['poNumber' => '002'])->getShippingTrackings();
        self::assertEquals(1, $order2Trackings->count());
        $order2Tracking = $order2Trackings->first();
        self::assertEquals('method 3', $order2Tracking->getMethod());
        self::assertEquals('number 3', $order2Tracking->getNumber());
    }

    public function testUpdateOrderAddresses()
    {
        $this->processUpdateList(
            Order::class,
            'update_list_add_addresses.yml'
        );

        $response = $this->cget(
            ['entity' => 'orders'],
            ['page[size]' => 2]
        );
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order->id)>',
                        'relationships' => [
                            'billingAddress'  => [
                                'data' => [
                                    'type' => 'orderaddresses',
                                    'id'   => 'new'
                                ]
                            ],
                            'shippingAddress' => [
                                'data' => [
                                    'type' => 'orderaddresses',
                                    'id'   => 'new'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'orders',
                        'id'            => '<toString(@simple_order2->id)>',
                        'relationships' => [
                            'billingAddress'  => [
                                'data' => [
                                    'type' => 'orderaddresses',
                                    'id'   => 'new'
                                ]
                            ],
                            'shippingAddress' => [
                                'data' => [
                                    'type' => 'orderaddresses',
                                    'id'   => 'new'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $repository = $this->getEntityManager()->getRepository(Order::class);

        /** @var Order $order1 */
        $order1 = $repository->find($this->getReference('simple_order')->getId());
        self::assertEquals('Billing 1', $order1->getBillingAddress()->getLabel());
        self::assertEquals('Shipping 1', $order1->getShippingAddress()->getLabel());

        /** @var Order $order2 */
        $order1 = $repository->find($this->getReference('simple_order2')->getId());
        self::assertEquals('Billing 2', $order1->getBillingAddress()->getLabel());
        self::assertEquals('Shipping 2', $order1->getShippingAddress()->getLabel());
    }

    public function testTryToCreateEntitiesWithErrors()
    {
        $operationId = $this->processUpdateList(
            Order::class,
            [
                'data'     => [
                    [
                        'type'          => 'orders',
                        'attributes'    => ['shipUntil' => 'wrong_date'],
                        'relationships' => [
                            'lineItems' => ['data' => [['type' => 'orderlineitems', 'id' => 'li1']]]
                        ]
                    ],
                ],
                'included' => [
                    [
                        'type'          => 'orderlineitems',
                        'id'            => 'li1',
                        'attributes'    => [
                            'quantity' => 123,
                            'currency' => 'USD',
                        ],
                        'relationships' => [
                            'product'     => [
                                'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                            ],
                            'productUnit' => [
                                'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                            ]
                        ]
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationErrors(
            [
                [
                    'id'     => $operationId.'-1-1',
                    'status' => 400,
                    'title'  => 'form constraint',
                    'detail' => 'The "wrong_date" is not valid date.',
                    'source' => ['pointer' => '/data/0/attributes/shipUntil']
                ],
                [
                    'id'     => $operationId.'-1-2',
                    'status' => 400,
                    'title'  => 'not blank constraint',
                    'detail' => 'Price value should not be blank. Source: price.',
                    'source' => ['pointer' => '/included/0']
                ],
                [
                    'id'     => $operationId.'-1-3',
                    'status' => 400,
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/0/relationships/customer/data']
                ],
                [
                    'id'     => $operationId.'-1-4',
                    'status' => 400,
                    'title'  => 'not blank constraint',
                    'detail' => 'Price value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/value']
                ]
            ],
            $operationId
        );
    }
}
