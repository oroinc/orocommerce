<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeAction($shoppingList, 'orob2b_shoppinglist_action_createorder');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $this->initClient([], $this->generateBasicAuthHeader());
        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $content = $crawler->filter('[data-ftid=orob2b_order_type_lineItems]')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $actionName
     */
    protected function executeAction(ShoppingList $shoppingList, $actionName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_action_execute',
                [
                    'actionName' => $actionName,
                    'route' => 'orob2b_shopping_list_view',
                    'entityId' => $shoppingList->getId(),
                    'entityClass' => 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList'
                ]
            )
        );
    }
}
