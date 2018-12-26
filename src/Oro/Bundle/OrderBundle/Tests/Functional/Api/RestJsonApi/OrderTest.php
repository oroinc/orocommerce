<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderTest extends RestJsonApiTestCase
{
    protected function setUp()
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
        self::assertSame('78.5000', $order->getTotal());
        self::assertEquals('USD', $order->getCurrency());
        self::assertNotEmpty($order->getOwner()->getId());
        self::assertEquals($organizationId, $order->getOrganization()->getId());
    }

    public function testUpdate()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $orderId = $order->getId();
        $oldSubtotalValue = $order->getSubtotal();
        $oldTotalValue = $order->getTotal();

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
        self::assertLessThanOrEqual($oldSubtotalValue, $updatedOrder->getSubtotal());
        self::assertLessThanOrEqual($oldTotalValue, $updatedOrder->getTotal());
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
