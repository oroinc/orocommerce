<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderShippingTrackingData;

class OrderShippingTrackingTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderShippingTrackingData::class,
            LoadCountryData::class,
            LoadRegionData::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'ordershippingtrackings']);

        $this->assertResponseContains('shipping_tracking_get_list.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            [
                'entity' => 'ordershippingtrackings',
                'id' => '<toString(@order_shipping_tracking.1->id)>',
            ]
        );
        $this->assertResponseContains('shipping_tracking_get.yml', $response);
    }

    public function testGetOrderRelationship()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $response = $this->getRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => $orderShippingTracking->getId(), 'association' => 'order']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Order::class),
                    'id' => (string)$this->getReference(LoadOrders::ORDER_1)->getId(),
                ],
            ],
            $response
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'ordershippingtrackings'],
            'shipping_tracking_create.yml'
        );

        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getEntityManager()
            ->getRepository(OrderShippingTracking::class)
            ->findOneBy(['method' => 'method 3']);

        self::assertSame('number 3', $orderShippingTracking->getNumber());
        self::assertSame(
            $this->getReference(LoadOrders::ORDER_1)->getId(),
            $orderShippingTracking->getOrder()->getId()
        );

        $this->getEntityManager()->remove($orderShippingTracking);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    public function testUpdateMethod()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $this->patch(
            ['entity' => 'ordershippingtrackings', 'id' => $orderShippingTracking->getId()],
            [
                'data' => [
                    'type' => 'ordershippingtrackings',
                    'id' => (string)$orderShippingTracking->getId(),
                    'attributes' => [
                        'method' => 'method 4',
                    ],
                ],
            ]
        );

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getEntityManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        self::assertSame('method 4', $updatedOrderShippingTracking->getMethod());
    }

    public function testUpdateNumber()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $this->patch(
            ['entity' => 'ordershippingtrackings', 'id' => $orderShippingTracking->getId()],
            [
                'data' => [
                    'type' => 'ordershippingtrackings',
                    'id' => (string)$orderShippingTracking->getId(),
                    'attributes' => [
                        'number' => 'number 4',
                    ],
                ],
            ]
        );

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getEntityManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        self::assertSame('number 4', $updatedOrderShippingTracking->getNumber());
    }

    public function testUpdateOrderRelationship()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::MY_ORDER);

        $this->patchRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => $orderShippingTracking->getId(), 'association' => 'order'],
            [
                'data' => [
                    'type' => $this->getEntityType(Order::class),
                    'id' => (string)$order->getId(),
                ],
            ]
        );

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getEntityManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        self::assertEquals($order->getId(), $updatedOrderShippingTracking->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);
        $orderShippingTrackingId = $orderShippingTracking->getId();

        $this->cdelete(
            ['entity' => 'ordershippingtrackings'],
            ['filter' => ['id' => $orderShippingTrackingId]]
        );

        $removedOrderShippingTracking = $this->getEntityManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTrackingId);

        self::assertNull($removedOrderShippingTracking);
    }
}
