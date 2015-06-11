<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\OrderBundle\Entity\Order;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        $orderId = $order->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_order', ['id' => $orderId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
