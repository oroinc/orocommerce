<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;

/**
 * @dbIsolation
 */
class OrderDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        $orderId = $order->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $orderId,
            $this->getContainer()->getParameter('orob2b_order.entity.order.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_order_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
