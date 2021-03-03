<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OrderDiscountTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroOrderBundle/Tests/Functional/Api/DataFixtures/order_discounts.yml']);
        /** @var TotalHelper $totalHelper */
        $totalHelper = self::getContainer()->get('oro_order.order.total.total_helper');
        $totalHelper->fill($this->getOrderReference('order1'));
        $this->getEntityManager()->flush();
        // guard
        $this->clearEntityManager();
        $this->assertOrderTotals($this->getOrderReference('order1'), '200.0000', '119.6000', '80.4000');
    }

    private function getOrderDiscountReference(string $reference): OrderDiscount
    {
        return $this->getReference($reference);
    }

    private function getOrderReference(string $reference): Order
    {
        return $this->getReference($reference);
    }

    private function getOrderDiscount(int $discountId): OrderDiscount
    {
        return $this->getEntityManager()->find(OrderDiscount::class, $discountId);
    }

    private function getOrder(int $orderId): Order
    {
        return $this->getEntityManager()->find(Order::class, $orderId);
    }

    private function getCreateOrderData(): array
    {
        return [
            'data'     => [
                'type'          => 'orders',
                'attributes'    => [
                    'identifier' => 'new_order',
                    'currency'   => 'USD'
                ],
                'relationships' => [
                    'lineItems'    => [
                        'data' => [['type' => 'orderlineitems', 'id' => 'line_item_1']]
                    ],
                    'customerUser' => [
                        'data' => ['type' => 'customerusers', 'id' => '<toString(@customer_user->id)>']
                    ],
                    'customer'     => [
                        'data' => ['type' => 'customers', 'id' => '<toString(@customer->id)>']
                    ],
                    'organization' => [
                        'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'orderlineitems',
                    'id'            => 'line_item_1',
                    'attributes'    => [
                        'quantity' => 30,
                        'value'    => '10.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'product'     => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product1->id)>']
                        ],
                        'productUnit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@item->code)>']
                        ]
                    ]
                ]
            ]
        ];
    }

    private function assertOrderDiscount(
        OrderDiscount $discount,
        string $type,
        string $amount,
        float $percent,
        ?string $description
    ): void {
        self::assertSame($type, $discount->getType(), 'Order Discount Type');
        self::assertSame($amount, $discount->getAmount(), 'Order Discount Amount');
        self::assertSame($percent, $discount->getPercent(), 'Order Discount Percent');
        self::assertSame($description, $discount->getDescription(), 'Order Discount Description');
    }

    private function assertOrderTotals(Order $order, string $subtotal, string $total, string $discount): void
    {
        self::assertSame($subtotal, $order->getSubtotal(), 'Order Subtotal');
        self::assertSame($total, $order->getTotal(), 'Order Total');
        $discounts = $order->getTotalDiscounts();
        self::assertSame($discount, null !== $discounts ? $discounts->getValue() : '0.0000', 'Order Discount');
        // guard
        self::assertSame(
            (float)$subtotal,
            (float)$total + (float)$discount,
            'Order Subtotal === Order Total + Order Discount'
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orderdiscounts']);

        $this->assertResponseContains('cget_discount.yml', $response);
    }

    public function testGet(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.percent')->getId();

        $response = $this->get(['entity' => 'orderdiscounts', 'id' => (string)$discountId]);

        $this->assertResponseContains('get_discount.yml', $response);
    }

    public function testCreateForAmountDiscountType(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'description'       => 'New Discount By Amount',
                    'amount'            => '15.0000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data);

        $discountId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '15.0000';
        $responseContent['data']['attributes']['percent'] = 0.075;
        $this->assertResponseContains($responseContent, $response);

        $this->assertOrderDiscount(
            $this->getOrderDiscount($discountId),
            OrderDiscount::TYPE_AMOUNT,
            '15.0000',
            7.5,
            'New Discount By Amount'
        );
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '104.6000', '95.4000');
    }

    public function testCreateForPercentDiscountType(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'description'       => 'New Discount By Percent',
                    'percent'           => 0.201,
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data);

        $discountId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $this->assertResponseContains($responseContent, $response);

        $this->assertOrderDiscount(
            $this->getOrderDiscount($discountId),
            OrderDiscount::TYPE_PERCENT,
            '40.2000',
            20.1,
            'New Discount By Percent'
        );
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '79.4000', '120.6000');
    }

    public function testCreateWithoutDescription(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '15.0000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data);

        $discountId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['percent'] = 0.075;
        $this->assertResponseContains($responseContent, $response);

        $this->assertOrderDiscount(
            $this->getOrderDiscount($discountId),
            OrderDiscount::TYPE_AMOUNT,
            '15.0000',
            7.5,
            null
        );
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '104.6000', '95.4000');
    }

    public function testCreateWithEmptyDescription(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'description'       => '',
                    'amount'            => '15.0000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data);

        $discountId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['percent'] = 0.075;
        $this->assertResponseContains($responseContent, $response);

        $this->assertOrderDiscount(
            $this->getOrderDiscount($discountId),
            OrderDiscount::TYPE_AMOUNT,
            '15.0000',
            7.5,
            ''
        );
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '104.6000', '95.4000');
    }

    public function testTryToCreateForUndefinedDiscountType(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'discount type constraint',
                'detail' => 'The discount type is invalid.'
                    . ' Valid types are: oro_order_discount_item_type_amount,oro_order_discount_item_type_percent',
                'source' => ['pointer' => '/data/attributes/orderDiscountType']
            ],
            $response
        );
    }

    public function testTryToCreateForInvalidDiscountType(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '1.0000',
                    'percent'           => 0.1,
                    'orderDiscountType' => 'another'
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'discount type constraint',
                'detail' => 'The discount type is invalid.'
                    . ' Valid types are: oro_order_discount_item_type_amount,oro_order_discount_item_type_percent',
                'source' => ['pointer' => '/data/attributes/orderDiscountType']
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWithoutAmountAndPercentValues(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/amount']
            ],
            $response
        );
    }

    public function testTryToCreateForPercentDiscountTypeWithoutAmountAndPercentValues(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/percent']
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWithoutAmountValueAndWithPercentValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'percent'           => 0.3,
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/amount']
            ],
            $response
        );
    }

    public function testTryToCreateForPercentDiscountTypeWithoutPercentValueAndWithAmountValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '30.0000',
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/percent']
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWithNotNumericAmountValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => 'invalid',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/amount']
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWithTooBigAmountValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '200.1000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'range constraint',
                    'detail' => 'This value should be between -100% and 100%.',
                    'source' => ['pointer' => '/data/attributes/percent']
                ],
                [
                    'title'  => 'discounts constraint',
                    'detail' => 'The sum of all discounts cannot exceed the order grand total amount.'
                        . ' Please review some discounts above and make necessary adjustments.',
                    'source' => ['pointer' => '/data/relationships/order/data/totalDiscountsAmount']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWhenSumOfAllDiscountsExceedsOrderTotal(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '119.7000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'discounts constraint',
                'detail' => 'The sum of all discounts cannot exceed the order grand total amount.'
                    . ' Please review some discounts above and make necessary adjustments.',
                'source' => ['pointer' => '/data/relationships/order/data/totalDiscountsAmount']
            ],
            $response
        );
    }

    public function testTryToCreateForPercentDiscountTypeWithNotNumericPercentValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'percent'           => 'invalid',
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/percent']
            ],
            $response
        );
    }

    public function testTryToCreateForAmountDiscountTypeWithNegativeAmountValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'amount'            => '-1.0000',
                    'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'range constraint',
                'detail' => 'This value should be 0 or more.',
                'source' => ['pointer' => '/data/attributes/amount']
            ],
            $response
        );
    }

    public function testTryToCreateForPercentDiscountTypeWithTooLowPercentValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'percent'           => -1.01,
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'range constraint',
                'detail' => 'This value should be between -100% and 100%.',
                'source' => ['pointer' => '/data/attributes/percent']
            ],
            $response
        );
    }

    public function testTryToCreateForPercentDiscountTypeWithTooHighPercentValue(): void
    {
        $orderId = $this->getOrderReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'attributes'    => [
                    'percent'           => 1.01,
                    'orderDiscountType' => OrderDiscount::TYPE_PERCENT
                ],
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$orderId]]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'orderdiscounts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'range constraint',
                'detail' => 'This value should be between -100% and 100%.',
                'source' => ['pointer' => '/data/attributes/percent']
            ],
            $response
        );
    }

    public function testUpdateDescription(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'description' => 'New Description'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $responseContent['data']['attributes']['percent'] = 0.201;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount(
            $discount,
            OrderDiscount::TYPE_AMOUNT,
            '40.2000',
            20.1,
            'New Description'
        );
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '119.6000', '80.4000');
    }

    public function testUpdateDescriptionToNull(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'description' => null
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $responseContent['data']['attributes']['percent'] = 0.201;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount(
            $discount,
            OrderDiscount::TYPE_AMOUNT,
            '40.2000',
            20.1,
            null
        );
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '119.6000', '80.4000');
    }

    public function testUpdateDescriptionToEmptyString(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'description' => ''
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['id'] = (string)$discountId;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $responseContent['data']['attributes']['percent'] = 0.201;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount(
            $discount,
            OrderDiscount::TYPE_AMOUNT,
            '40.2000',
            20.1,
            ''
        );
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '119.6000', '80.4000');
    }

    public function testUpdateAmountValue(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'amount' => '15.0000'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['attributes']['percent'] = 0.075;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount($discount, OrderDiscount::TYPE_AMOUNT, '15.0000', 7.5, 'Discount 2');
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '144.8000', '55.2000');
    }

    public function testUpdatePercentValue(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.percent')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'percent' => 0.5
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['attributes']['amount'] = '100.0000';
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount($discount, OrderDiscount::TYPE_PERCENT, '100.0000', 50, 'Discount 1');
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '59.8000', '140.2000');
    }

    public function testUpdateAmountValueForPercentDiscountType(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.percent')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'amount' => '15.0000'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $responseContent['data']['attributes']['percent'] = 0.201;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount($discount, OrderDiscount::TYPE_PERCENT, '40.2000', 20.1, 'Discount 1');
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '119.6000', '80.4000');
    }

    public function testUpdatePercentValueForAmountDiscountType(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'percent' => 0.5
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['attributes']['amount'] = '40.2000';
        $responseContent['data']['attributes']['percent'] = 0.201;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        $this->assertOrderDiscount($discount, OrderDiscount::TYPE_AMOUNT, '40.2000', 20.1, 'Discount 2');
        $this->assertOrderTotals($discount->getOrder(), '200.0000', '119.6000', '80.4000');
    }

    public function testTryToUpdateAmountValueWhenItIsTooBig(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'amount' => '200.1000'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'range constraint',
                    'detail' => 'This value should be between -100% and 100%.',
                    'source' => ['pointer' => '/data/attributes/percent']
                ],
                [
                    'title'  => 'discounts constraint',
                    'detail' => 'The sum of all discounts cannot exceed the order grand total amount.'
                        . ' Please review some discounts above and make necessary adjustments.',
                    'source' => ['pointer' => '/data/relationships/order/data/totalDiscountsAmount']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateAmountValueWhenSumOfAllDiscountsExceedsOrderTotal(): void
    {
        $discountId = $this->getOrderDiscountReference('order_discount.amount')->getId();
        $data = [
            'data' => [
                'type'       => 'orderdiscounts',
                'id'         => (string)$discountId,
                'attributes' => [
                    'amount' => '159.9000'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'discounts constraint',
                'detail' => 'The sum of all discounts cannot exceed the order grand total amount.'
                    . ' Please review some discounts above and make necessary adjustments.',
                'source' => ['pointer' => '/data/relationships/order/data/totalDiscountsAmount']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();

        $this->delete(['entity' => 'orderdiscounts', 'id' => (string)$discountId]);

        self::assertTrue(null === $this->getEntityManager()->find(OrderDiscount::class, $discountId));
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '159.8000', '40.2000');
    }

    public function testDeleteList(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();

        $this->cdelete(['entity' => 'orderdiscounts'], ['filter' => ['id' => $discountId]]);

        self::assertTrue(null === $this->getEntityManager()->find(OrderDiscount::class, $discountId));
        $this->assertOrderTotals($this->getOrder($orderId), '200.0000', '159.8000', '40.2000');
    }

    public function testGetSubResourceForOrder(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();
        $orderPoNumber = $discount->getOrder()->getPoNumber();

        $response = $this->getSubresource(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orders',
                    'id'         => (string)$orderId,
                    'attributes' => [
                        'poNumber' => $orderPoNumber
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForOrder(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testTryToMoveDiscountToAnotherOrder(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();
        $targetOrderId = $this->getOrderReference('order2')->getId();
        self::assertNotEquals($orderId, $targetOrderId);
        $data = [
            'data' => [
                'type'          => 'orderdiscounts',
                'id'            => (string)$discountId,
                'relationships' => [
                    'order' => ['data' => ['type' => 'orders', 'id' => (string)$targetOrderId]]
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orderdiscounts', 'id' => (string)$discountId], $data);

        $responseContent = $data;
        $responseContent['data']['relationships']['order']['data']['id'] = (string)$orderId;
        $this->assertResponseContains($responseContent, $response);

        $discount = $this->getOrderDiscount($discountId);
        self::assertSame($orderId, $discount->getOrder()->getId());
    }

    public function testTryToMoveDiscountToAnotherOrderViaUpdateRelationship(): void
    {
        $discount = $this->getOrderDiscountReference('order_discount.amount');
        $discountId = $discount->getId();
        $orderId = $discount->getOrder()->getId();
        $targetOrderId = $this->getOrderReference('order2')->getId();
        self::assertNotEquals($orderId, $targetOrderId);

        $response = $this->patchRelationship(
            ['entity' => 'orderdiscounts', 'id' => (string)$discountId, 'association' => 'order'],
            [
                'data' => [
                    'type' => 'orders',
                    'id'   => (string)$targetOrderId
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToMoveDiscountToAnotherOrderViaCreateOrderResource(): void
    {
        $data = $this->getCreateOrderData();
        $data['data']['relationships']['discounts'] = [
            'data' => [
                ['type' => 'orderdiscounts', 'id' => '<toString(@order_discount.amount->id)>']
            ]
        ];

        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'The discount cannot be moved to another order.',
                'source' => ['pointer' => '/data/relationships/discounts/data/0']
            ],
            $response
        );
    }

    public function testTryToMoveDiscountToAnotherOrderViaUpdateOrderResource(): void
    {
        $data = [
            'data' => [
                'type'          => 'orders',
                'id'            => '<toString(@order2->id)>',
                'relationships' => [
                    'discounts' => [
                        'data' => [['type' => 'orderdiscounts', 'id' => '<toString(@order_discount.amount->id)>']]
                    ]
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'orders', 'id' => '<toString(@order2->id)>'], $data, [], false);

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'The discount cannot be moved to another order.',
                'source' => ['pointer' => '/data/relationships/discounts/data/0']
            ],
            $response
        );
    }

    public function testCreateOrderWithDiscount(): void
    {
        $data = $this->getCreateOrderData();
        $data['data']['relationships']['discounts'] = [
            'data' => [
                ['type' => 'orderdiscounts', 'id' => 'discount_1'],
                ['type' => 'orderdiscounts', 'id' => 'discount_2']
            ]
        ];
        $data['included'][] = [
            'type'       => 'orderdiscounts',
            'id'         => 'discount_1',
            'attributes' => [
                'amount'            => '15.0000',
                'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
            ]
        ];
        $data['included'][] = [
            'type'       => 'orderdiscounts',
            'id'         => 'discount_2',
            'attributes' => [
                'percent'           => 0.1,
                'orderDiscountType' => OrderDiscount::TYPE_PERCENT
            ]
        ];

        $response = $this->post(['entity' => 'orders'], $data);

        $orderId = (int)$this->getResourceId($response);
        $this->assertOrderTotals($this->getOrder($orderId), '300.0000', '255.0000', '45.0000');
    }

    public function testTryToCreateOrderWithToBigDiscount(): void
    {
        $data = $this->getCreateOrderData();
        $data['data']['relationships']['discounts'] = [
            'data' => [
                ['type' => 'orderdiscounts', 'id' => 'discount_1'],
                ['type' => 'orderdiscounts', 'id' => 'discount_2']
            ]
        ];
        $data['included'][] = [
            'type'       => 'orderdiscounts',
            'id'         => 'discount_1',
            'attributes' => [
                'amount'            => '301.0000',
                'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
            ]
        ];
        $data['included'][] = [
            'type'       => 'orderdiscounts',
            'id'         => 'discount_2',
            'attributes' => [
                'amount'            => '10.0000',
                'orderDiscountType' => OrderDiscount::TYPE_AMOUNT
            ]
        ];

        $response = $this->post(['entity' => 'orders'], $data, [], false);

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'range constraint',
                    'detail' => 'This value should be between -100% and 100%.',
                    'source' => ['pointer' => '/included/1/attributes/percent']
                ],
                [
                    'title'  => 'discounts constraint',
                    'detail' => 'The sum of all discounts cannot exceed the order grand total amount.'
                        . ' Please review some discounts above and make necessary adjustments.',
                    'source' => ['pointer' => '/included/1/relationships/order/data']
                ]
            ],
            $response
        );
    }
}
