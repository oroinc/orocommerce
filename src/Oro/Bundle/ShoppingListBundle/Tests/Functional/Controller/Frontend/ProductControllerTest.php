<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadShoppingListLineItems::class,
        ]);
    }

    public function testProductAddToShoppingListForm()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $content = $crawler->html();

        $shoppingListClass = ShoppingList::class;

        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->getShoppingLists();
        /** @var ShoppingList $shoppingListFromAnotherSite */
        $shoppingListFromAnotherSite = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9);

        foreach ($shoppingLists as $shoppingList) {
            if ($shoppingList !== $shoppingListFromAnotherSite) {
                static::assertStringContainsString('Add to ' . $shoppingList->getLabel(), $content);
            } else {
                static::assertStringNotContainsString('Add to ' . $shoppingList->getLabel(), $content);
            }
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);

        $this->assertCount(1, $shoppingList->getLineItems());

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_add_product',
                [
                    'productId'      => $product->getId(),
                    'shoppingListId' => $shoppingList->getId()
                ]
            ),
            [
                'oro_product_frontend_line_item' => [
                    'quantity' => 5,
                    'unit'     => 'liter',
                    '_token'   => $this->getCsrfToken('oro_product_frontend_line_item')->getValue()
                ]
            ]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(
            'Product has been added to "<a href="' .
            $this->getUrl('oro_shopping_list_frontend_update', ['id' => $shoppingList->getId()]) .
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
