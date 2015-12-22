<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListFrontendActionsTest extends FrontendActionTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreateOrder()
    {
        if (!$this->client->getContainer()->hasParameter('orob2b_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeAction($shoppingList, 'orob2b_shoppinglist_frontend_action_createorder');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_frontend_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $content = $crawler->filter('[data-ftid=orob2b_order_frontend_type_lineItems]')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    public function testCreateRequest()
    {
        if (!$this->client->getContainer()->hasParameter('orob2b_rfp.entity.request.class')) {
            $this->markTestSkipped('RFPBundle disabled');
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeAction($shoppingList, 'orob2b_shoppinglist_frontend_action_request_quote');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertStringStartsWith(
            $this->getUrl('orob2b_rfp_frontend_request_create', [ProductDataStorage::STORAGE_KEY => 1]),
            $data['redirectUrl']
        );

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $lineItems = $crawler->filter('[data-ftid=orob2b_rfp_frontend_request_requestProducts]');
        $this->assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    public function testLineItemUpdate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $crawler = $this->assertActionForm(
            'orob2b_shoppinglist_frontend_updatelineitem',
            $lineItem->getId(),
            get_class($lineItem)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action[lineItem][quantity]' => 33.3,
                'oro_action[lineItem][unit]' => $unit->getCode(),
                'oro_action[lineItem][notes]' => 'Updated test notes',
            ]
        );

        $this->assertActionFormSubmitted($form, 'Line item has been updated');
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $actionName
     */
    protected function executeAction(ShoppingList $shoppingList, $actionName)
    {
        $this->assertExecuteAction(
            $actionName,
            $shoppingList->getId(),
            'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['route' => 'orob2b_shopping_list_frontend_view']
        );
    }
}
