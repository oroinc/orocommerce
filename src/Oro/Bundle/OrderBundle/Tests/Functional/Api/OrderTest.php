<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Symfony\Component\HttpFoundation\Response;

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
        $response = $this->cget(['entity' => $this->getEntityType(Order::class)]);
        $this->assertResponseContains(__DIR__.'/responses/order/get_orders.yml', $response);
    }


    public function testGet()
    {
        $response = $this->get([
            'entity' => $this->getEntityType(Order::class),
            'id' => '<toString(@simple_order->id)>',
        ]);

        $this->assertResponseContains(__DIR__.'/responses/order/get_order.yml', $response);
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
            ['entity' => $this->getEntityType(Order::class)],
            __DIR__.'/responses/order/create_order.yml'
        );
        $createdOrder = $this->getFirstOrder();
        /** @var Order $item */
        $order = $this->getManager()
            ->getRepository(Order::class)
            ->findOneBy(['identifier' => 'new_order']);

        static::assertEquals('2345678', $order->getPoNumber());
        static::assertEquals(78.5, $order->getTotal());
        static::assertSame('USD', $order->getCurrency());
        static::assertNotEmpty($order->getOwner()->getId());
        static::assertEquals($createdOrder->getOrganization()->getId(), $order->getOrganization()->getId());


        $this->removeOrder($order);
    }

    public function testUpdate()
    {
        $order = $this->getFirstOrder();

        $oldSubtotalValue = $order->getSubtotal();
        $oldTotalValue = $order->getTotal();

        $newNotes = 'test notes';

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => (string)$order->getId(),
                'attributes' => [
                    'customerNotes' => $newNotes,
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(Order::class),
                'id' => $order->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        $this->post(
            ['entity' => $this->getEntityType(OrderLineItem::class)],
            __DIR__.'/responses/line_item/create_with_product_sku.yml'
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getManager()
            ->getRepository(Order::class)
            ->find($order->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertEquals($newNotes, $updatedOrder->getCustomerNotes());
        static::assertLessThanOrEqual($oldSubtotalValue, $updatedOrder->getSubtotal());
        static::assertLessThanOrEqual($oldTotalValue, $updatedOrder->getTotal());
    }

    public function testDeleteByFilter()
    {
        $order = $this->getFirstOrder();
        $orderId = $order->getId();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(Order::class)]
        );
        $response = $this->request(
            'DELETE',
            $uri,
            ['filter' => ['id' => $orderId]]
        );

        $this->getManager()->clear();

        $removedDiscount = $this->getManager()
            ->getRepository(Order::class)
            ->find($orderId);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        static::assertNull($removedDiscount);
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
        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(Order::class),
                'id' => $entityId,
                'association' => $associationName,
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                'type' => $this->getEntityType($associationClassName),
                'id' => (string)$expectedAssociationId,
            ],
        ];

        static::assertEquals($expected, $content);
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $expectedAssociationId
     */
    private function assertGetSubResource($entityId, $associationName, $expectedAssociationId)
    {
        $uri = $this->getUrl(
            'oro_rest_api_get_subresource',
            [
                'entity' => $this->getEntityType(Order::class),
                'id' => $entityId,
                'association' => $associationName,
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $resource = json_decode($response->getContent(), true)['data'];

        static::assertEquals($expectedAssociationId, $resource['id']);
    }

    /**
     * @param Order $order
     */
    private function removeOrder(Order $order)
    {
        $this->getManager()->remove($order);
        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    /**
     * @return Order
     */
    private function getFirstOrder()
    {
        return $this->getReference(LoadOrders::ORDER_1);
    }

    /**
     * @return ObjectManager
     */
    private function getManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
