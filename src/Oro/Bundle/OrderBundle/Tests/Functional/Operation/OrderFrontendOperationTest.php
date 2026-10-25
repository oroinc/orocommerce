<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Operation;

use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\HttpFoundation\Response;

class OrderFrontendOperationTest extends FrontendActionTestCase
{
    #[\Override]
    protected function setUp(): void
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
            Order::class,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            Response::HTTP_NOT_FOUND
        );

        $this->assertEquals(
            [
                'message' => 'Operation with name "DELETE" not found',
                'success' => false
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
