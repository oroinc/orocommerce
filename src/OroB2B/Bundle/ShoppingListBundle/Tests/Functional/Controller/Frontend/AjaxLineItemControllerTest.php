<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class AjaxLineItemControllerTest extends WebTestCase
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
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testAddProductFromView()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $this->client->request(
            'POST',
            $this->getUrl('orob2b_shopping_list_frontend_add_product', ['productId' => $product->getId()]),
            [
                'orob2b_shopping_list_frontend_line_item' => [
                    'quantity' => 110,
                    'unit' => $unit->getCode(),
                    '_token' => $this->getCsrfToken(),
                ],
            ]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);
    }

    public function testAddProductFromViewNotValidData()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $this->client->request(
            'POST',
            $this->getUrl('orob2b_shopping_list_frontend_add_product', ['productId' => $product->getId()]),
            [
                'orob2b_shopping_list_frontend_line_item' => [
                    'quantity' => null,
                    'unit' => null,
                    '_token' => $this->getCsrfToken(),
                ],
            ]
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);
    }

    public function testAddProductsMassAction()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_add_products_massaction',
                [
                    'gridName' => 'frontend-products-grid',
                    'actionName' => 'orob2b_shoppinglist_frontend_addlineitemlist' . $shoppingList->getId(),
                    'shoppingList' => $shoppingList->getId(),
                    'inset' => 1,
                    'values' => $this->getReference('product.1')->getId()
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(1, $result['count']);
    }

    public function testAddProductsToNewMassAction()
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $shoppingListsCount = count($this->getShoppingListRepository()->findAll());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_add_products_to_new_massaction',
                [
                    'gridName' => 'frontend-products-grid',
                    'actionName' => 'orob2b_shoppinglist_frontend_addlineitemnew',
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Create and Add')->form();
        $form['orob2b_shopping_list_type[label]'] = 'TestShoppingList';

        $this->client->request(
            $form->getMethod(),
            $this->getUrl(
                'orob2b_shopping_list_add_products_to_new_massaction',
                [
                    'gridName' => 'frontend-products-grid',
                    'actionName' => 'orob2b_shoppinglist_frontend_addlineitemnew',
                    'inset' => 1,
                    'values' => $product->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            ),
            $form->getPhpValues()
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $shoppingLists = $this->getShoppingListRepository()->findBy([], ['id' => 'DESC']);

        $this->assertCount($shoppingListsCount + 1, $shoppingLists);

        /** @var ShoppingList $shoppingList */
        $shoppingList = reset($shoppingLists);
        $lineItems = $shoppingList->getLineItems();

        $this->assertCount(1, $lineItems);

        /** @var LineItem $lineItem */
        $lineItem = $lineItems->first();
        $this->assertEquals($product->getId(), $lineItem->getProduct()->getId());
    }

    /**
     * @return string
     */
    protected function getCsrfToken()
    {
        return $this->client
            ->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('orob2b_shopping_list_frontend_line_item')
            ->getValue();
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getShoppingListRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:ShoppingList');
    }
}
