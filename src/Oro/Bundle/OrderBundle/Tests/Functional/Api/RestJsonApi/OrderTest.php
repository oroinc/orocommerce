<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class OrderTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderLineItems::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orders']);

        $this->assertResponseContains('order_get_list.yml', $response);
    }


    public function testGet()
    {
        $response = $this->get([
            'entity' => 'orders',
            'id' => '<toString(@simple_order->id)>',
        ]);

        $this->assertResponseContains('order_get.yml', $response);
    }

    public function testGetSubResources()
    {
        $order = $this->getFirstOrder();

        $this->assertGetSubResource($order->getId(), 'owner', $order->getOwner()->getId());
        $this->assertGetSubResource($order->getId(), 'organization', $order->getOrganization()->getId());
        $this->assertGetSubResource($order->getId(), 'customerUser', $order->getCustomerUser()->getId());
        $this->assertGetSubResource($order->getId(), 'customer', $order->getCustomer()->getId());
    }

    public function testGetRelationships()
    {
        $order = $this->getFirstOrder();

        $this->assertGetRelationship($order->getId(), 'owner', User::class, $order->getOwner()->getId());
        $this->assertGetRelationship($order->getId(), 'customer', Customer::class, $order->getCustomer()->getId());
        $this->assertGetRelationship(
            $order->getId(),
            'organization',
            Organization::class,
            $order->getOrganization()->getId()
        );
        $this->assertGetRelationship(
            $order->getId(),
            'customerUser',
            CustomerUser::class,
            $order->getCustomerUser()->getId()
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'orders'],
            'order_create.yml'
        );
        $createdOrder = $this->getFirstOrder();
        /** @var Order $item */
        $order = $this->getEntityManager()
            ->getRepository(Order::class)
            ->findOneBy(['identifier' => 'new_order']);

        self::assertEquals('2345678', $order->getPoNumber());
        self::assertEquals(78.5, $order->getTotal());
        self::assertSame('USD', $order->getCurrency());
        self::assertNotEmpty($order->getOwner()->getId());
        self::assertEquals($createdOrder->getOrganization()->getId(), $order->getOrganization()->getId());

        $this->removeOrder($order);
    }

    public function testUpdate()
    {
        $order = $this->getFirstOrder();

        $oldSubtotalValue = $order->getSubtotal();
        $oldTotalValue = $order->getTotal();

        $newNotes = 'test notes';

        $this->patch(
            ['entity' => 'orders', 'id' => $order->getId()],
            [
                'data' => [
                    'type' => 'orders',
                    'id' => (string)$order->getId(),
                    'attributes' => [
                        'customerNotes' => $newNotes,
                    ],
                    'relationships' => [
                        'paymentTerm' => [
                            'data' => [
                                'type' => 'paymentterms',
                                'id' => '<toString(@payment_term.net_20->id)>'
                            ]
                        ]
                    ]
                ],
            ]
        );

        //!!!!!
        $this->post(
            ['entity' => $this->getEntityType(OrderLineItem::class)],
            'line_item_create_with_product_sku.yml'
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()
            ->getRepository(Order::class)
            ->find($order->getId());

        $paymentTermProvider = $this->getContainer()->get('oro_payment_term.provider.payment_term');

        self::assertEquals($newNotes, $updatedOrder->getCustomerNotes());
        self::assertEquals('net 20', $paymentTermProvider->getObjectPaymentTerm($updatedOrder)->getLabel());
        self::assertLessThanOrEqual($oldSubtotalValue, $updatedOrder->getSubtotal());
        self::assertLessThanOrEqual($oldTotalValue, $updatedOrder->getTotal());
    }

    public function testDeleteByFilter()
    {
        $order = $this->getFirstOrder();
        $orderId = $order->getId();

        $this->cdelete(
            ['entity' => 'orders'],
            ['filter' => ['id' => $orderId]]
        );

        $removedDiscount = $this->getEntityManager()
            ->getRepository(Order::class)
            ->find($orderId);

        self::assertNull($removedDiscount);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $associationClassName
     * @param string $expectedAssociationId
     */
    private function assertGetRelationship(
        $entityId,
        $associationName,
        $associationClassName,
        $expectedAssociationId
    ) {
        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => $entityId, 'association' => $associationName]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType($associationClassName),
                    'id' => (string)$expectedAssociationId,
                ],
            ],
            $response
        );
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $expectedAssociationId
     */
    private function assertGetSubResource($entityId, $associationName, $expectedAssociationId)
    {
        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => $entityId, 'association' => $associationName]
        );

        $result = json_decode($response->getContent(), true);

        self::assertEquals($expectedAssociationId, $result['data']['id']);
    }

    /**
     * @param Order $order
     */
    private function removeOrder(Order $order)
    {
        $this->getEntityManager()->remove($order);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    /**
     * @return Order
     */
    private function getFirstOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }
}
