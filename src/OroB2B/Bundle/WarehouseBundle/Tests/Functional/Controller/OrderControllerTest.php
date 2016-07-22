<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesWithOrders;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures(
            [
                LoadWarehousesWithOrders::class
            ]
        );
    }

    public function testViewOrderShouldDisplayWarehouseField()
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $this->client->request('GET', $this->getUrl('orob2b_order_view', ['id' => $order->getId()]));

        $this->assertContains('Warehouse', $this->client->getResponse()->getContent());
        $this->assertContains($order->getWarehouse()->getName(), $this->client->getResponse()->getContent());
    }

    public function testEditOrderWarehouse()
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_update', ['id' => $order->getId()]));

        $form = $crawler->selectButton('Save')->form();
        $warehouse2 = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE2);
        $form['orob2b_order_type[warehouse]'] = $warehouse2->getId();
        $this->client->submit($form);

        $this->client->request('GET', $this->getUrl('orob2b_order_view', ['id' => $order->getId()]));
        $this->assertContains('Warehouse', $this->client->getResponse()->getContent());
        $this->assertContains($warehouse2->getName(), $this->client->getResponse()->getContent());
    }
}
