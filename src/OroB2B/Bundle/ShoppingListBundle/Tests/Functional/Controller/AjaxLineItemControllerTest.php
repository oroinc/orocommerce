<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * @dbIsolation
 */
class AjaxLineItemControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreate()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference('shopping_list');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product2 */
        $product = $this->getReference('product.2');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_line_item_create_widget',
                [
                    'shoppingListId' => $shoppingList->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_shopping_list_line_item[product]' => $product->getId(),
                'orob2b_shopping_list_line_item[quantity]' => 22.2,
                'orob2b_shopping_list_line_item[notes]' => 'test_notes',
                'orob2b_shopping_list_line_item[unit]' => $unit->getCode()
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdate()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_line_item_update_widget',
                [
                    'id' => $lineItem->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_shopping_list_line_item[quantity]' => 33.3,
                'orob2b_shopping_list_line_item[notes]' => 'Updated test notes',
            ]
        );

        $this->assertSaved($form);
    }

    /**
     * @param Form $form
     */
    protected function assertSaved(Form $form)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*\d+/i', $html);
    }
}
