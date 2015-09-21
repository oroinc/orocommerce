<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
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

    public function testProductAddToShoppingListForm()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProducts::PRODUCT_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_product_shopping_list_form', ['productId' => $product->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $content = $crawler->html();

        $shoppingListClass = $this->getContainer()->getParameter('orob2b_shopping_list.entity.shopping_list.class');

        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getContainer()->get('doctrine')->getRepository($shoppingListClass)->findAll();

        foreach ($shoppingLists as $shoppingList) {
            $this->assertContains('Add to ' . $shoppingList->getLabel(), $content);
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);

        $this->assertCount(0, $shoppingList->getLineItems());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_line_item_frontend_add_widget',
                ['productId' => $product->getId(), '_wid' => 1]
            )
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $form = $crawler->selectButton('Save')->form();

        $form['orob2b_shopping_list_frontend_line_item_widget[quantity]'] = 5;
        $form['orob2b_shopping_list_frontend_line_item_widget[unit]'] = 'liter';
        $form['orob2b_shopping_list_frontend_line_item_widget[shoppingList]'] = $shoppingList->getId();
        $this->client->submit($form);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getContainer()->get('doctrine')->getManagerForClass($shoppingListClass)
            ->find($shoppingListClass, $shoppingList->getId());

        $this->assertCount(1, $shoppingList->getLineItems());

        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        $this->assertEquals(5, $lineItem->getQuantity());
        $this->assertEquals('liter', $lineItem->getUnit()->getCode());
    }
}
