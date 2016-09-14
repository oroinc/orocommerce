<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ShoppingListControllerTest extends WebTestCase
{
    const TEST_LABEL1 = 'Shopping list label 1';
    const TEST_LABEL2 = 'Shopping list label 2';
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_b2b_rfp.frontend_product_visibility';

    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );

        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    public function testView()
    {
        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        // assert current shopping list
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view')
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($currentShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        $this->assertNotContains('Request Quote', $crawler->html());
        $this->assertNotContains('Create Order', $crawler->html());
    }

    /**
     * @dataProvider testViewSelectedShoppingListDataProvider
     * @param string $shoppingList
     * @param string $expectedLineItemPrice
     * @param bool $needToTestRequestQuote
     */
    public function testViewSelectedShoppingListWithLineItemPrice(
        $shoppingList,
        $expectedLineItemPrice,
        $needToTestRequestQuote
    ) {
        // assert selected shopping list
        /** @var ShoppingList $shoppingList1 */
        $shoppingList1 = $this->getReference($shoppingList);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList1->getId()])
        );

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $availableInventoryStatuses = [$this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find(Product::INVENTORY_STATUS_IN_STOCK)];

        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains($shoppingList1->getLabel(), $crawler->html());

        $this->assertEquals((strpos($crawler->html(), 'Request Quote') !== false), $needToTestRequestQuote);

        $this->assertContains('Create Order', $crawler->html());
        $this->assertLineItemPriceEquals($expectedLineItemPrice, $crawler);
    }

    /**
     * @return array
     */
    public function testViewSelectedShoppingListDataProvider()
    {
        return [
            'price defined' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                'expectedLineItemPrice' => '$13.10',
                'needToTestRequestQuote' => true
            ],
            'no price for selected quantity' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_3,
                'expectedLineItemPrice' => 'N/A',
                'needToTestRequestQuote' => false
            ],
            'zero price' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_4,
                'expectedLineItemPrice' => '$0.00',
                'needToTestRequestQuote' => true
            ],
            'no price for selected unit' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
                'expectedLineItemPrice' => [
                    'N/A',
                    '$0.00',
                ],
                'needToTestRequestQuote' => true
            ],
        ];
    }

    public function testQuickAdd()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $products = [[
            'productSku' => $product->getSku(),
            'productQuantity' => 15
        ]];

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getContainer()
            ->get('oro_shopping_list.shopping_list.manager')
            ->getForCurrentUser();

        $this->assertQuickAddFormSubmitted($crawler, $products);//add to current
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 15);
        $this->assertQuickAddFormSubmitted($crawler, $products, $currentShoppingList->getId());//add to specific
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 30);
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
        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();
        $processor = $this->getContainer()->get('oro_shopping_list.processor.quick_add');

        $this->client->followRedirects(true);

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName(),
                    'additional' => $shippingListId
                ]
            ]
        );

        $expectedMessage = $this->getContainer()
            ->get('translator')
            ->transChoice('oro.shoppinglist.actions.add_success_message', count($products));

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
        $items = $this->getContainer()->get('doctrine')->getManagerForClass('OroShoppingListBundle:LineItem')
            ->getRepository('OroShoppingListBundle:LineItem')
            ->findBy(['shoppingList' => $shoppingList]);

        $this->assertCount(1, $items);
        $item = $items[0];

        $this->assertEquals($sku, $item->getProductSku());
        $this->assertEquals($quantity, $item->getQuantity());
    }

    /**
     * @param $expected
     * @param Crawler $crawler
     */
    protected function assertLineItemPriceEquals($expected, Crawler $crawler)
    {
        $expected = (array)$expected;
        $prices = $crawler->filter('[data-name="price-value"]');
        $this->assertSameSize($expected, $prices);
        foreach ($prices as $value) {
            $this->assertContains(trim($value->nodeValue), $expected);
        }
    }

    protected function tearDown()
    {
        $this->configManager->reset(self::RFP_PRODUCT_VISIBILITY_KEY);
        $this->configManager->flush();
    }
}
