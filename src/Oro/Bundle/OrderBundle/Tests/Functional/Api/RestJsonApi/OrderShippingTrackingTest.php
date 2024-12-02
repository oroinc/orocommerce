<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderShippingTrackingTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroOrderBundle/Tests/Functional/Api/DataFixtures/order_shipping_tracking.yml']);

        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Order::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::BASIC_LEVEL,
                'DELETE' => AccessLevel::BASIC_LEVEL
            ]
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'ordershippingtrackings']);

        $this->assertResponseContains('cget_shipping_tracking.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'ordershippingtrackings', 'id' => '<toString(@order_shipping_tracking.1->id)>']
        );

        $this->assertResponseContains('get_shipping_tracking.yml', $response);
    }

    public function testTryToGetForUnaccessibleOrder(): void
    {
        $response = $this->get(
            ['entity' => 'ordershippingtrackings', 'id' => '<toString(@order_shipping_tracking.3->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate(): void
    {
        $orderId = $this->getReference('order1')->getId();
        $data = [
            'data' => [
                'type'          => 'ordershippingtrackings',
                'attributes'    => [
                    'method' => 'method 3',
                    'number' => 'number 3'
                ],
                'relationships' => [
                    'orders' => [
                        'data' => [[
                            'type' => 'orders',
                            'id'   => (string)$orderId
                        ]]
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

    public function testTryToCreateForUnaccessibleOrder(): void
    {
        $response = $this->post(
            ['entity' => 'ordershippingtrackings'],
            [
                'data' => [
                    'type'          => 'ordershippingtrackings',
                    'attributes'    => [
                        'method' => 'method 3',
                        'number' => 'number 3'
                    ],
                    'relationships' => [
                        'orders' => ['data' => [['type' => 'orders', 'id' => '<toString(@order3->id)>']]]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access granted constraint',
                'detail' => 'The "VIEW" permission is denied for the related resource.',
                'source' => ['pointer' => '/data/relationships/orders/data/0']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testUpdateMethod(): void
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

    public function testUpdateNumber(): void
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

    public function testTryToUpdateForUnaccessibleOrder(): void
    {
        $response = $this->patch(
            ['entity' => 'ordershippingtrackings', 'id' => '<toString(@order_shipping_tracking.3->id)>'],
            [
                'data' => [
                    'type'       => 'ordershippingtrackings',
                    'id'         => '<toString(@order_shipping_tracking.3->id)>',
                    'attributes' => [
                        'method' => 'method 4'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDelete(): void
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();

        $this->delete(['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId]);

        self::assertTrue(null === $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId));
    }

    public function testDeleteForUnaccessibleOrder(): void
    {
        $response = $this->delete(
            ['entity' => 'ordershippingtrackings', 'id' => '<toString(@order_shipping_tracking.3->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();

        $this->cdelete(
            ['entity' => 'ordershippingtrackings'],
            ['filter' => ['id' => (string)$shippingTrackingId]]
        );

        self::assertTrue(null === $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId));
    }

    public function testDeleteListForUnaccessibleOrder(): void
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.3')->getId();

        $this->cdelete(
            ['entity' => 'ordershippingtrackings'],
            ['filter' => ['id' => (string)$shippingTrackingId]]
        );

        self::assertTrue(null !== $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId));
    }

    public function testGetRelationshipForOrder(): void
    {
        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getReference('order_shipping_tracking.1');
        $shippingTrackingId = $shippingTracking->getId();
        $orderId = $shippingTracking->getOrder()->getId();

        $response = $this->getRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId, 'association' => 'orders']
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'orders', 'id' => (string)$orderId]]],
            $response
        );
    }

    public function testUpdateRelationshipForOrder(): void
    {
        $shippingTrackingId = $this->getReference('order_shipping_tracking.1')->getId();
        $targetOrderId = $this->getReference('order2')->getId();

        $this->patchRelationship(
            ['entity' => 'ordershippingtrackings', 'id' => (string)$shippingTrackingId, 'association' => 'orders'],
            [
                'data' => [[
                    'type' => 'orders',
                    'id'   => (string)$targetOrderId
                ]]
            ]
        );

        /** @var OrderShippingTracking $shippingTracking */
        $shippingTracking = $this->getEntityManager()->find(OrderShippingTracking::class, $shippingTrackingId);
        self::assertEquals($targetOrderId, $shippingTracking->getOrder()->getId());
    }
}
