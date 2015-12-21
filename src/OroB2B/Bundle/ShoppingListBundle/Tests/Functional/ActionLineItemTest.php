<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ActionLineItemTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreate()
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

        $this->assertFormSubmitted($form, 'Line item has been added');
    }

    public function testCreateDuplicate()
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

        $this->assertFormSubmitted($form, 'Line item has been added');
    }

    public function testUpdate()
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

        $this->assertFormSubmitted($form, 'Line item has been updated');
    }

    /**
     * @param string $actionName
     * @param int $entityId
     * @param string $entityClass
     * @param array $data
     * @return Crawler
     */
    protected function assertActionForm($actionName, $entityId, $entityClass, array $data = [])
    {
        $url = $this->getUrl('oro_action_widget_form', array_merge([
                'actionName' => $actionName,
                'entityId' => $entityId,
                'entityClass' => $entityClass,
                '_widgetContainer' => 'dialog',
                '_wid' => 'test-uuid',
        ], $data));

        $server = array_merge($this->generateBasicAuthHeader(), [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $crawler = $this->client->request('GET', $url, [], [], $server);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @param Form $form
     * @param string $message
     */
    protected function assertFormSubmitted(Form $form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($message, $crawler->html());
    }
}
