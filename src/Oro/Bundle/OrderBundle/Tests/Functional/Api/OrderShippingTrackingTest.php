<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderShippingTrackingData;
use Symfony\Component\HttpFoundation\Response;

class OrderShippingTrackingTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadOrderShippingTrackingData::class,
                LoadCountryData::class,
                LoadRegionData::class,
            ]
        );
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => $this->getEntityType(OrderShippingTracking::class)]);

        $this->assertResponseContains(
            __DIR__ . '/responses/shippingTracking/get_shipping_tracking_items.yml',
            $response
        );
    }

    public function testGet()
    {
        $response = $this->get(
            [
                'entity' => $this->getEntityType(OrderShippingTracking::class),
                'id' => '<toString(@order_shipping_tracking.1->id)>',
            ]
        );
        $this->assertResponseContains(__DIR__ . '/responses/shippingTracking/get_shipping_tracking.yml', $response);
    }

    public function testGetOrderRelationship()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(OrderShippingTracking::class),
                'id' => $orderShippingTracking->getId(),
                'association' => 'order',
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => $this->getReference(LoadOrders::ORDER_1)->getId(),
            ],
        ];

        static::assertEquals($expected, $content);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => $this->getEntityType(OrderShippingTracking::class)],
            __DIR__ . '/responses/shippingTracking/create_shipping_tracking.yml'
        );

        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getManager()
            ->getRepository(OrderShippingTracking::class)
            ->findOneBy(['method' => 'method 3']);

        static::assertSame('number 3', $orderShippingTracking->getNumber());
        static::assertSame(
            $this->getReference(LoadOrders::ORDER_1)->getId(),
            $orderShippingTracking->getOrder()->getId()
        );

        $this->getManager()->remove($orderShippingTracking);
        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    /**
     * @return ObjectManager
     */
    private function getManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    public function testUpdateMethod()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderShippingTracking::class),
                'id' => (string)$orderShippingTracking->getId(),
                'attributes' => [
                    'method' => 'method 4',
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderShippingTracking::class),
                'id' => $orderShippingTracking->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('method 4', $updatedOrderShippingTracking->getMethod());
    }

    public function testUpdateNumber()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderShippingTracking::class),
                'id' => (string)$orderShippingTracking->getId(),
                'attributes' => [
                    'number' => 'number 4',
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderShippingTracking::class),
                'id' => $orderShippingTracking->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('number 4', $updatedOrderShippingTracking->getNumber());
    }

    public function testUpdateOrderRelationship()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::MY_ORDER);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(OrderShippingTracking::class),
                'id' => $orderShippingTracking->getId(),
                'association' => 'order',
            ]
        );
        $data = [
            'data' => [
                'type' => $this->getEntityType(Order::class),
                'id' => (string)$order->getId(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        /** @var OrderShippingTracking $updatedOrderShippingTracking */
        $updatedOrderShippingTracking = $this->getManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTracking->getId());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertEquals($order->getId(), $updatedOrderShippingTracking->getOrder()->getId());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderShippingTracking $orderShippingTracking */
        $orderShippingTracking = $this->getReference(LoadOrderShippingTrackingData::ORDER_SHIPPING_TRACKING_1);
        $orderShippingTrackingId = $orderShippingTracking->getId();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(OrderShippingTracking::class)]
        );
        $response = $this->request(
            'DELETE',
            $uri,
            ['filter' => ['id' => $orderShippingTrackingId]]
        );

        $this->getManager()->clear();

        $removedOrderShippingTracking = $this->getManager()
            ->getRepository(OrderShippingTracking::class)
            ->find($orderShippingTrackingId);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        static::assertNull($removedOrderShippingTracking);
    }
}
