<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutUserACLData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as BaseLoadCustomerData;
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
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class ShoppingListControllerTest extends WebTestCase
{
    const TEST_LABEL1 = 'Shopping list label 1';
    const TEST_LABEL2 = 'Shopping list label 2';
    const RFP_PRODUCT_VISIBILITY_KEY = 'oro_rfp.frontend_product_visibility';
    const SHOPPING_LIST_AVAIL_FOR_GUEST_KEY = 'oro_shopping_list.availability_for_guests';

    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadProductUnitPrecisions::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadShoppingListACLData::class,
                LoadCheckoutUserACLData::class,
            ]
        );

        $this->configManager = $this->getContainer()->get('oro_config.manager');

        $this->configManager->set(self::SHOPPING_LIST_AVAIL_FOR_GUEST_KEY, true);
        $this->configManager->flush();
    }

    public function testViewWhenNoId(): void
    {
        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC);
        $this->getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->setCurrent($user, $currentShoppingList);

        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_view'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString($currentShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        static::assertStringNotContainsString('Request Quote', $crawler->html());
        static::assertStringNotContainsString('Create Order', $crawler->html());
    }

    public function testIndexWhenDisabledAndEnabled(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_index'));
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 404);

        $this->enableMyShoppingListsPage(true);

        $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_index'));
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->enableMyShoppingListsPage(false);
    }

    /**
     * @param bool $isEnabled
     */
    private function enableMyShoppingListsPage(bool $isEnabled): void
    {
        $configManager = $this->getContainer()->get(ConfigManager::class);
        $configManager->set('oro_shopping_list.my_shopping_lists_page_enabled', $isEnabled);
        $configManager->flush();
    }

    public function testIndex(): void
    {
        $this->enableMyShoppingListsPage(true);

        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('my-shopping-lists-grid', $crawler->html());

        $response = $this->client->requestFrontendGrid(
            'my-shopping-lists-grid',
            ['my-shopping-lists-grid[customer_user_id]' => $user->getId()],
            true
        );

        $data = $this->getJsonResponseContent($response, 200)['data'];

        $this->assertCount(1, $data);
        $this->assertArrayHasKey('label', $data[0]);
        $this->assertEquals('shopping_list_customer1_user_basic', $data[0]['label']);

        $this->enableMyShoppingListsPage(false);
    }

    public function testView(): void
    {
        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC);
        $this->getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->setCurrent($user, $currentShoppingList);

        // assert current shopping list
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $currentShoppingList->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString($currentShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        static::assertStringNotContainsString('Request Quote', $crawler->html());
        static::assertStringNotContainsString('Create Order', $crawler->html());
    }

    public function testAccessDeniedForShoppingListsFromAnotherWebsite()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9);

        $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    /**
     * @dataProvider viewSelectedShoppingListDataProvider
     * @param string $shoppingList
     * @param string|array $expectedLineItemPrice
     * @param bool   $atLeastOneAvailableProduct
     */
    public function testViewSelectedShoppingListWithLineItemPrice(
        string $shoppingList,
        $expectedLineItemPrice,
        bool $atLeastOneAvailableProduct
    ) {
        /** @var ShoppingList $shoppingList1 */
        $shoppingList1 = $this->getReference($shoppingList);

        // Make sure that all products are enabled for calculations
        /** @var PersistentCollection $lineItems */
        $lineItems = $shoppingList1->getLineItems();
        $lineItems->forAll(function ($i, LineItem $lineItem) {
            $lineItem->getProduct()->setStatus(Product::STATUS_ENABLED);
        });
        self::getContainer()->get('doctrine')->getManagerForClass(Product::class)->flush();
        $lineItems->setInitialized(false);

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                BaseLoadCustomerData::AUTH_USER,
                BaseLoadCustomerData::AUTH_PW
            )
        );

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
        static::assertStringContainsString($shoppingList1->getLabel(), $crawler->html());

        static::assertStringContainsString('Create Order', $crawler->html());
        if ($atLeastOneAvailableProduct) {
            static::assertStringContainsString('Duplicate List', $crawler->html());
            static::assertStringContainsString('Request Quote', $crawler->html());
        }

        $this->assertLineItemPriceEquals($expectedLineItemPrice, $crawler);
    }

    /**
     * @return array
     */
    public function viewSelectedShoppingListDataProvider()
    {
        return [
            'price defined' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                'expectedLineItemPrice' => '$13.10',
                'atLeastOneAvailableProduct' => true,
            ],
            'zero price' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_4,
                'expectedLineItemPrice' => '$0.00',
                'atLeastOneAvailableProduct' => false,
            ],
            'no price for selected unit' => [
                'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
                'expectedLineItemPrice' => [
                    '$0.00',
                    'Price for requested quantity is not available',
                ],
                'atLeastOneAvailableProduct' => true,
            ],
        ];
    }

    public function testViewSelectedShoppingListWithoutLineItemPrice()
    {
        // assert selected shopping list
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                BaseLoadCustomerData::AUTH_USER,
                BaseLoadCustomerData::AUTH_PW
            )
        );
        $this->configManager->set(
            'oro_product.general_frontend_product_visibility',
            ['in_stock', 'out_of_stock', 'discontinued']
        );
        $this->configManager->flush();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        $availableInventoryStatuses = [$this->getContainer()->get('doctrine')->getRepository($inventoryStatusClassName)
            ->find(Product::INVENTORY_STATUS_IN_STOCK)];

        $this->configManager->set(self::RFP_PRODUCT_VISIBILITY_KEY, $availableInventoryStatuses);
        $this->configManager->flush();

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString($shoppingList->getLabel(), $crawler->html());

        static::assertStringContainsString('Create Order', $crawler->html());

        $this->assertLineItemPriceEquals('Price for requested quantity is not available', $crawler);
    }

    public function testQuickAdd()
    {
        $this->markTestSkipped(
            'Waiting for new quick order page to be finished'
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $products = [[
            'productSku' => $product->getSku(),
            'productQuantity' => 15,
            'productUnit' => 'kg'
        ]];

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->getForCurrentUser();

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
     * @param bool $expectedCreateOrderButtonVisible
     */
    public function testACL($route, $resource, $user, $status, $expectedCreateOrderButtonVisible)
    {
        $this->initClient([], $this->generateBasicAuthHeader($user, $user));

        /* @var $resource ShoppingList */
        $resource = $this->getReference($resource);

        $url = $this->getUrl($route, ['id' => $resource->getId()]);
        $crawler = $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);

        if (200 === $response->getStatusCode()) {
            if ($expectedCreateOrderButtonVisible) {
                static::assertStringContainsString('Create Order', $crawler->html());
            } else {
                static::assertStringNotContainsString('Create Order', $crawler->html());
            }
        }
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'CREATE anon' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => '',
                'status' => 401,
                'expectedCreateOrderButtonVisible' => false
            ],
            'VIEW (anonymous user)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => '',
                'status' => 401,
                'expectedCreateOrderButtonVisible' => false
            ],
            'VIEW (user from another customer)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
                'expectedCreateOrderButtonVisible' => false
            ],
            'VIEW (user from parent customer : DEEP_VIEW_ONLY)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 200,
                'expectedCreateOrderButtonVisible' => false
            ],
            'VIEW (user from parent customer : LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
                'expectedCreateOrderButtonVisible' => true
            ],
            'VIEW (user from same customer : LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
                'expectedCreateOrderButtonVisible' => false
            ],
            'VIEW (BASIC)' => [
                'route' => 'oro_shopping_list_frontend_view',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
                'expectedCreateOrderButtonVisible' => false
            ],
            'CREATE (user with create: LOCAL)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 200,
                'expectedCreateOrderButtonVisible' => false
            ],
            'CREATE (user with create: NONE)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'status' => 403,
                'expectedCreateOrderButtonVisible' => false
            ],
            'CREATE (BASIC)' => [
                'route' => 'oro_shopping_list_frontend_create',
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
                'expectedCreateOrderButtonVisible' => false
            ],
        ];
    }

    public function testViewListForGuest()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_view'));
        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        static::assertStringNotContainsString('Create Order', $crawler->html());
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
            ->trans('oro.shoppinglist.actions.add_success_message', ['%count%' => count($products)]);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString($expectedMessage, $crawler->html());

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

        $this->assertCount(2, $items);
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
        $prices = $crawler->filter('[data-name="prices-hint"]');
        $this->assertSameSize($expected, $prices);
        foreach ($prices as $value) {
            $this->assertContains(trim($value->textContent), $expected);
        }
    }

    protected function tearDown(): void
    {
        $this->configManager->reset(self::RFP_PRODUCT_VISIBILITY_KEY);
        $this->configManager->reset(self::SHOPPING_LIST_AVAIL_FOR_GUEST_KEY);
        $this->configManager->flush();
    }
}
