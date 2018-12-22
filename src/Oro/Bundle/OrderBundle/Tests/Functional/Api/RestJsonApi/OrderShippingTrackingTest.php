<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolationPerTest
 */
class OrderShippingTrackingTest extends RestJsonApiTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_shipping_tracking.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'ordershippingtrackings']);

        $this->assertResponseContains('cget_shipping_tracking.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'ordershippingtrackings', 'id' => '<toString(@order_shipping_tracking.1->id)>']
        );

        $this->assertResponseContains('get_shipping_tracking.yml', $response);
    }

    public function testCreate()
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();
        $data = [
            'data' => [
                'type'          => 'ordershippingtrackings',
                'attributes'    => [
                    'method' => 'method 3',
                    'number' => 'number 3'
                ],
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'orders',
                            'id'   => (string)$orderId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'ordershippingtrackings'],
            $data
        );

        $shippingTrackingId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['id'] = (string)$shippingTrackingId;
        $this->assertResponseContains($responseContent, $response);

        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertEquals('method 3', $shippingTracking->getMethod());
        self::assertEquals('number 3', $shippingTracking->getNumber());
        self::assertEquals($orderId, $shippingTracking->getOrder()->getId());
    }

    public function testUpdateMethod()
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();

        $this->patch(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId],
            [
                'data' => [
                    'type'       => 'ordershippingtrackings',
                    'id'         => (string)$shippingTrackingId,
                    'attributes' => [
                        'method' => 'method 4'
                    ]
                ]
            ]
        );

        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertEquals('method 4', $shippingTracking->getMethod());
    }

    public function testUpdateNumber()
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();

        $this->patch(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId],
            [
                'data' => [
                    'type'       => 'ordershippingtrackings',
                    'id'         => (string)$shippingTrackingId,
                    'attributes' => [
                        'number' => 'number 4'
                    ]
                ]
            ]
        );

        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertEquals('number 4', $shippingTracking->getNumber());
    }

    public function testDeleteList()
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();

        $this->cdelete(
            ['entity' => 'ordershippingtrackings'],
            ['filter' => ['id' => (string)$shippingTrackingId]]
        );

        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertTrue(null === $shippingTracking);
    }

    public function testGetRelationshipForOrder()
    {
        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getReference('order_shipping_tracking.1');
        $shippingTrackingId = $shippingTracking->getId();
        $orderId = $shippingTracking->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId, 'association' => 'order']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orders', 'id' => (string)$orderId]],
            $response
        );
    }

    public function testUpdateRelationshipForOrder()
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();
        $targetOrderId = $this->getReference(LoadOrders::MY_ORDER)->getId();

        $this->patchRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId, 'association' => 'order'],
            [
                'data' => [
                    'type' => 'orders',
                    'id'   => (string)$targetOrderId
                ]
            ]
        );

        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertEquals($targetOrderId, $shippingTracking->getOrder()->getId());
    }
}
