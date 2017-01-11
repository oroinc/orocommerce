<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Operation;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolation
 */
class OrderFrontendOperationTest extends FrontendActionTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $this->assertExecuteOperation(
            'DELETE',
            $order->getId(),
            $this->getContainer()->getParameter('oro_order.entity.order.class'),
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            Response::HTTP_NOT_FOUND
        );

        $this->assertEquals(
            [
                'success' => false,
                'message' => 'Operation with name "DELETE" not found',
                'messages' => [],
                'refreshGrid' => null,
                'flashMessages' => []
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
