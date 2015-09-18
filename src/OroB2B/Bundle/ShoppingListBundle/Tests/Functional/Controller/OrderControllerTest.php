<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        if (!$this->client->getContainer()->hasParameter('orob2b_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_create_order', ['id' => $shoppingList->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 302);

        $crawler = $this->client->followRedirect();
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_create'),
            $this->client->getRequest()->getRequestUri()
        );
        $this->assertEquals(true, $this->client->getRequest()->get(ProductDataStorage::STORAGE_KEY));

        $content = $crawler->filter('[data-ftid=orob2b_order_type_lineItems]')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }
}
