<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolationPerTest
 */
class OrderWithExternalStatusManagementTest extends RestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroOrderBundle/Tests/Functional/DataFixtures/order_line_items.yml'
        ]);
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', true);
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set('oro_order.order_enable_external_status_management', false);
        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'orders']);

        $responseContent = $this->getResponseData('cget_order.yml');
        $responseContent['data'][2]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'open'
        ];
        $responseContent['data'][3]['relationships']['status']['data'] = [
            'type' => 'orderstatuses',
            'id'   => 'wait_for_approval'
        ];
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'orders'],
            'create_order.yml'
        );

        /** @var Order $item */
        $order = $this->getEntityManager()->find(Order::class, (int)$this->getResourceId($response));
        self::assertEquals('open', $order->getStatus()->getId());
    }

    public function testUpdate(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $this->patch(
            ['entity' => 'orders', 'id' => $orderId],
            [
                'data' => [
                    'type'          => 'orders',
                    'id'            => (string)$orderId,
                    'relationships' => [
                        'status' => [
                            'data' => [
                                'type' => 'orderstatuses',
                                'id'   => 'open'
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('open', $updatedOrder->getStatus()->getId());
    }

    public function testGetSubresourceForStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_3)->getId();

        $response = $this->getSubresource(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'orderstatuses',
                    'id'         => 'open',
                    'attributes' => [
                        'name'     => 'Open',
                        'priority' => 1,
                        'default'  => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForStatus(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_3)->getId();

        $response = $this->getRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'orderstatuses', 'id' => 'open']],
            $response
        );
    }

    public function testUpdateStatusViaRelationship(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_1)->getId();

        $this->patchRelationship(
            ['entity' => 'orders', 'id' => (string)$orderId, 'association' => 'status'],
            ['data' => ['type' => 'orderstatuses', 'id' => 'open']]
        );

        /** @var Order $updatedOrder */
        $updatedOrder = $this->getEntityManager()->find(Order::class, $orderId);
        self::assertEquals('open', $updatedOrder->getStatus()->getId());
    }
}
