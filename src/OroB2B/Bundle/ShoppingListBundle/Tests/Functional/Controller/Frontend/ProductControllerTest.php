<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
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
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
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

        $this->assertCount(1, $shoppingList->getLineItems());

        $tokenManager = $this->getContainer()->get('security.csrf.token_manager');

        $this->client->request(
            'POST',
            $this->getUrl(
                'orob2b_shopping_list_frontend_add_product',
                [
                    'productId' => $product->getId(),
                    'shoppingListId' => $shoppingList->getId()
                ]
            ),
            [
                'orob2b_product_frontend_line_item' => [
                    'quantity' => 5,
                    'unit' => 'liter',
                    '_token' => $tokenManager->getToken('orob2b_product_frontend_line_item')->getValue()
                ]
            ]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(
            'Product has been added to "<a href="' .
            $this->getUrl('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()]) .
            '">'.$shoppingList->getLabel().'</a>"',
            $result['message']
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getContainer()->get('doctrine')->getManagerForClass($shoppingListClass)
            ->find($shoppingListClass, $shoppingList->getId());

        $this->assertCount(2, $shoppingList->getLineItems());

        /** @var LineItem $lineItem */
        $lineItem = $shoppingList->getLineItems()->first();
        $this->assertEquals(5, $lineItem->getQuantity());
        $this->assertEquals('bottle', $lineItem->getUnit()->getCode());
    }
}
