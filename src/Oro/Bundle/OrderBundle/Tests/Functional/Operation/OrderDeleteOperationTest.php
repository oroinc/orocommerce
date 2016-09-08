<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;

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
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');

        $this->assertDeleteOperation($order->getId(), 'oro_order.entity.order.class', 'orob2b_order_index');
    }
}
