<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as BaseLoadAccountData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ShoppingListControllerTest extends WebTestCase
{
    const TEST_LABEL1 = 'Shopping list label 1';
    const TEST_LABEL2 = 'Shopping list label 2';
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';

    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(BaseLoadAccountData::AUTH_USER, BaseLoadAccountData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadProductUnitPrecisions::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadShoppingListACLData::class,
            ]
        );

        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    public function testView()
    {
        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->loginUser(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC);
        $this->getContainer()->get('oro_shopping_list.shopping_list.manager')->setCurrent($user, $currentShoppingList);

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
     * @param $expectedCreateOrderButtonVisible
     */
    public function testViewSelectedShoppingListWithLineItemPrice(
        $shoppingList,
        $expectedLineItemPrice,
        $needToTestRequestQuote,
        $expectedCreateOrderButtonVisible
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

        if ($expectedCreateOrderButtonVisible) {
            $this->assertContains('Create Order', $crawler->html());
        } else {
            $this->assertNotContains('Create Order', $crawler->html());
        }

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
                'needToTestRequestQuote' => true,
                'expectedCreateOrderButtonVisible' => true
            ],
            'no price for selected quantity' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_3,
                'expectedLineItemPrice' => 'N/A',
                'needToTestRequestQuote' => false,
                'expectedCreateOrderButtonVisible' => false
            ],
            'zero price' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_4,
                'expectedLineItemPrice' => '$0.00',
                'needToTestRequestQuote' => true,
                'expectedCreateOrderButtonVisible' => true
            ],
            'no price for selected unit' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
                'expectedLineItemPrice' => [
                    'N/A',
                    '$0.00',
                ],
                'needToTestRequestQuote' => true,
                'expectedCreateOrderButtonVisible' => true
            ],
        ];
    }

    public function testQuickAdd()
    {
        $shoppingListManager = $this->getContainer()
            ->get('oro_shopping_list.shopping_list.manager');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $products = [[
            'productSku' => $product->getSku(),
            'productQuantity' => 15
        ]];

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $shoppingListManager->getForCurrentUser();

        $this->assertQuickAddFormSubmitted($crawler, $products);//add to current
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 15);
        $this->assertQuickAddFormSubmitted($crawler, $products, $currentShoppingList->getId());//add to specific
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 30);
    }
    /**
     * @group frontend-ACL
     * @dataProvider ACLProvider
     *
     * @param string $route
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testACL($route, $resource, $user, $status)
    {
        $this->loginUser($user);

        /* @var $resource ShoppingList */
        $resource = $this->getReference($resource);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ShoppingList::class);
        $em->getRepository(ShoppingList::class);

        $url = $this->getUrl($route, ['id' => $resource->getId()]);
        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'VIEW (anonymous user)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'VIEW (user from another account)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'VIEW (user from parent account : DEEP_VIEW_ONLY)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 200,
            ],
            'VIEW (user from parent account : LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'VIEW (user from same account : LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'VIEW (BASIC)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
            ],
            'CREATE anon' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'CREATE (user with create: LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 200,
            ],
            'CREATE (user with create: NONE)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'status' => 403,
            ],
            'CREATE (BASIC)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
            ],
        ];
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
            ->findBy(['shoppingList' => $shoppingList], ['id' => 'DESC']);

        $this->assertCount(3, $items);
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
