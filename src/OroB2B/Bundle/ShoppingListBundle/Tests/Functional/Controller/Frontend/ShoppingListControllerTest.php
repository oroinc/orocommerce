<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ShoppingListControllerTest extends WebTestCase
{
    const TEST_LABEL1 = 'Shopping list label 1';
    const TEST_LABEL2 = 'Shopping list label 2';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testView()
    {
        /** @var ShoppingList $selectedShoppingList */
        $selectedShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        // assert current shopping list
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_view')
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($currentShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        $this->assertNotContains('Request Quote', $crawler->html());
        $this->assertNotContains('Create Order', $crawler->html());

        // assert selected shopping list
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_view', ['id' => $selectedShoppingList->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($selectedShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        $this->assertContains('Request Quote', $crawler->html());
        $this->assertContains('Create Order', $crawler->html());
    }

    public function testQuickAdd()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $products = [[
            'productSku' => $product->getSku(),
            'productQuantity' => 15
        ]];

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getContainer()
            ->get('orob2b_shopping_list.shopping_list.manager')
            ->getForCurrentUser();

        $this->assertQuickAddFormSubmitted($crawler, $products);//add to current
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 15);
        $this->assertQuickAddFormSubmitted($crawler, $products, $currentShoppingList->getId());//add to specific
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 30);
    }

    public function testWidget()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_widget')
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertContains('"alias":"shopping_lists_frontend_widget"', $crawler->html());
        $this->assertContains('"elementFirst":false', $crawler->html());
    }

    /**
     * @param Crawler $crawler
     * @param array $products
     * @param int|null $shippingListId
     * @return Crawler
     */
    protected function assertQuickAddFormSubmitted(
        Crawler $crawler,
        array $products,
        $shippingListId = null
    ) {
        $form = $crawler->filter('form[name="orob2b_product_quick_add"]')->form();
        $processor = $this->getContainer()->get('orob2b_shopping_list.processor.quick_add');

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'orob2b_product_quick_add' => [
                    '_token' => $form['orob2b_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName(),
                    'additional' => $shippingListId
                ]
            ]
        );

        $expectedMessage = $this->getContainer()
            ->get('translator')
            ->transChoice('orob2b.shoppinglist.actions.add_success_message', count($products));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($expectedMessage, $crawler->html());

        return $crawler;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $sku
     * @param int $quantity
     */
    protected function assertShoppingListItemSaved(ShoppingList $shoppingList, $sku, $quantity)
    {
        /** @var LineItem[] $items */
        $items = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BShoppingListBundle:LineItem')
            ->getRepository('OroB2BShoppingListBundle:LineItem')
            ->findBy(['shoppingList' => $shoppingList]);

        $this->assertCount(1, $items);
        $item = $items[0];

        $this->assertEquals($sku, $item->getProductSku());
        $this->assertEquals($quantity, $item->getQuantity());
    }
}
