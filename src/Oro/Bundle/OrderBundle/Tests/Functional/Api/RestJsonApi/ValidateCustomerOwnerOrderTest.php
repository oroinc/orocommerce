<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

final class ValidateCustomerOwnerOrderTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);
    }

    public function testCreateOrderWithCustomerUserNotBelongingToCustomer(): void
    {
        $data = $this->getRequestData('create_order.yml');

        $data['data']['relationships']['customerUser']['data'] = [
            'type' => 'customerusers',
            'id'   => (string)$this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL)->getId()
        ];
        $data['data']['relationships']['customer']['data'] = [
            'type' => 'customers',
            'id'   => (string)$this->getReference(LoadCustomers::CUSTOMER_LEVEL_1)->getId()
        ];

        $response = $this->post(
            ['entity' => 'orders'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'customer owner constraint',
            ],
            $response
        );
    }

    public function testPatchOrderWithCustomerUserNotBelongingToCustomer(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();

        $data = [
            'data' => [
                'type'          => 'orders',
                'id'            => (string)$orderId,
                'relationships' => [
                    'customerUser' => [
                        'data' => [
                            'type' => 'customerusers',
                            'id'   => (string)$this->getReference(LoadCustomerUserData::LEVEL_1_1_EMAIL)->getId()
                        ]
                    ],
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id'   => (string)$this->getReference(LoadCustomers::CUSTOMER_LEVEL_1)->getId()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'customer owner constraint',
            ],
            $response
        );
    }
}
