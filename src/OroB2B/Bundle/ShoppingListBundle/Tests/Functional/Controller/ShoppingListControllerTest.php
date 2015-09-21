<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_shopping_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_view', ['id' => $shoppingList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($shoppingList->getLabel(), $html);
    }
}
