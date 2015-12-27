<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListActionsTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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

    public function testLineItemCreate()
    {
        /* @var $shoppingList ShoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /* @var $unit ProductUnit */
        $unit = $this->getReference('product_unit.bottle');
        /* @var $product2 Product */
        $product = $this->getReference('product.2');

        $crawler = $this->assertActionForm(
            'orob2b_shoppinglist_addlineitem',
            $shoppingList->getId(),
            get_class($shoppingList)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action[lineItem][product]' => $product->getId(),
                'oro_action[lineItem][quantity]' => 22.2,
                'oro_action[lineItem][notes]' => 'test_notes',
                'oro_action[lineItem][unit]' => $unit->getCode()
            ]
        );

        $this->assertActionFormSubmitted($form, 'Line item has been added');
    }

    public function testLineItemCreateDuplicate()
    {
        /* @var $lineItem LineItem  */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $shoppingList = $lineItem->getShoppingList();

        $crawler = $this->assertActionForm(
            'orob2b_shoppinglist_addlineitem',
            $shoppingList->getId(),
            get_class($shoppingList)
        );

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_action[lineItem][product]' => $lineItem->getProduct()->getId(),
                'oro_action[lineItem][quantity]' => 100,
                'oro_action[lineItem][notes]' => 'test_notes',
                'oro_action[lineItem][unit]' => $lineItem->getUnit()->getCode()
            ]
        );

        $this->assertActionFormSubmitted($form, 'Line item has been added');
    }

    public function testLineItemUpdate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $crawler = $this->assertActionForm(
            'orob2b_shoppinglist_updatelineitem',
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
            ['route' => 'orob2b_shopping_list_view']
        );
    }
}
