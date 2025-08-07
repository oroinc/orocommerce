<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ControllerFrontend;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutUserACLData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
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
    use ConfigManagerAwareTestTrait;

    private const TEST_LABEL1 = 'Shopping list label 1';
    private const TEST_LABEL2 = 'Shopping list label 2';

    private ?array $initialVisibility;
    private ?array $initialGeneralVisibility;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
            LoadShoppingListACLData::class,
            LoadCheckoutUserACLData::class,
        ]);

        $configManager = self::getConfigManager();
        $this->initialVisibility = $configManager->get('oro_rfp.frontend_product_visibility');
        $this->initialGeneralVisibility = $configManager->get('oro_product.general_frontend_product_visibility');
        $configManager->set('oro_shopping_list.availability_for_guests', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_rfp.frontend_product_visibility', $this->initialVisibility);
        $configManager->set('oro_product.general_frontend_product_visibility', $this->initialGeneralVisibility);
        $configManager->set('oro_shopping_list.availability_for_guests', false);
        $configManager->flush();
    }

    public function testViewWhenNoId(): void
    {
        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC);
        self::getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->setCurrent($user, $currentShoppingList);

        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_view'));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString($currentShoppingList->getLabel(), $crawler->html());
        // operations only for ShoppingList with LineItems
        self::assertStringNotContainsString('Request Quote', $crawler->html());
        self::assertStringNotContainsString('Checkout', $crawler->html());
    }

    public function testIndex(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(BaseLoadCustomerData::AUTH_USER, BaseLoadCustomerData::AUTH_PW)
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_index'));

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('frontend-customer-user-shopping-lists-grid', $crawler->html());

        $response = $this->client->requestFrontendGrid('frontend-customer-user-shopping-lists-grid', [], true);

        $data = self::getJsonResponseContent($response, 200)['data'];

        $expectedLabels = [
            LoadShoppingLists::SHOPPING_LIST_1 . '_label',
            LoadShoppingLists::SHOPPING_LIST_2 . '_label',
            LoadShoppingLists::SHOPPING_LIST_3 . '_label',
            LoadShoppingLists::SHOPPING_LIST_4 . '_label',
            LoadShoppingLists::SHOPPING_LIST_5 . '_label',
            LoadShoppingLists::SHOPPING_LIST_8 . '_label',
        ];

        self::assertCount(6, $data);
        self::assertCount(0, array_filter($data, static function (array $row) use ($expectedLabels) {
            return !\in_array($row['label'], $expectedLabels, true);
        }));
    }

    public function testViewGrid(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(BaseLoadCustomerData::AUTH_USER, BaseLoadCustomerData::AUTH_PW)
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('frontend-customer-user-shopping-list-grid', $crawler->html());

        $response = $this->client->requestFrontendGrid(
            'frontend-customer-user-shopping-list-grid',
            ['frontend-customer-user-shopping-list-grid[shopping_list_id]' => $shoppingList->getId()],
            true
        );

        $data = self::getJsonResponseContent($response, 200)['data'];

        self::assertCount(1, $data);
        self::assertArrayHasKey('item', $data[0]);
        self::assertEquals('product-1', $data[0]['sku']);
        self::assertEquals('prod_inventory_status.in_stock', $data[0]['inventoryStatus']);
        self::assertEquals(8, $data[0]['quantity']);
        self::assertEquals('bottle', $data[0]['unit']);
        self::assertEquals('$13.10', $data[0]['price']);
        self::assertEquals('$104.80', $data[0]['subtotal']);
        self::assertEquals('product-1.names.default', $data[0]['name']);
        self::assertEquals('Test Notes', $data[0]['notes']);
        self::assertFalse($data[0]['isConfigurable']);
        self::assertFalse($data[0]['isUpcoming']);
        self::assertNull($data[0]['availabilityDate']);
        self::assertNull($data[0]['subData']);
    }

    public function testView(): void
    {
        $user = $this->getReference(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC);
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC
            )
        );

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC);
        self::getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->setCurrent($user, $currentShoppingList);

        // assert current shopping list
        $this->requestShoppingListPage('oro_shopping_list_frontend_view', $currentShoppingList->getId());

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 200);

        $content = self::jsonToArray($response->getContent());
        $pageCrawler = new Crawler($content['page_content']);
        $buttonsCrawler = new Crawler($content['combined_button_wrapper']);

        self::assertStringContainsString($currentShoppingList->getLabel(), $pageCrawler->html());

        // operations only for ShoppingList with LineItems
        self::assertEquals(0, $buttonsCrawler->count());
    }

    public function testAccessDeniedForShoppingListsFromAnotherWebsite(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
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
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    /**
     * @dataProvider viewSelectedShoppingListDataProvider
     */
    public function testViewSelectedShoppingListWithLineItemPrice(
        string $shoppingList,
        string|array $expectedLineItemPrice,
        bool $atLeastOneAvailableProduct
    ): void {
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
            self::generateBasicAuthHeader(BaseLoadCustomerData::AUTH_USER, BaseLoadCustomerData::AUTH_PW)
        );

        $this->requestShoppingListPage('oro_shopping_list_frontend_view', $shoppingList1->getId());

        $availableInventoryStatuses = [
            self::getContainer()->get('doctrine')->getRepository(EnumOption::class)
                ->find(ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_IN_STOCK
                ))
        ];

        $configManager = self::getConfigManager();
        $configManager->set('oro_rfp.frontend_product_visibility', $availableInventoryStatuses);
        $configManager->flush();

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 200);

        $content = self::jsonToArray($response->getContent());
        $pageCrawler = new Crawler($content['page_content']);
        $buttonsCrawler = new Crawler($content['combined_button_wrapper']);

        self::assertStringContainsString($shoppingList1->getLabel(), $pageCrawler->html());

        self::assertStringContainsString('Checkout', $buttonsCrawler->html());
        if ($atLeastOneAvailableProduct) {
            self::assertStringContainsString('Request Quote', $buttonsCrawler->html());
        }

        $response = $this->client->requestFrontendGrid(
            'frontend-customer-user-shopping-list-grid',
            ['frontend-customer-user-shopping-list-grid[shopping_list_id]' => $shoppingList1->getId()],
            true
        );

        self::assertJsonResponseStatusCodeEquals($response, 200);

        $data = self::jsonToArray($response->getContent())['data'];

        $this->assertLineItemPriceEquals($expectedLineItemPrice, $data);
    }

    public function viewSelectedShoppingListDataProvider(): array
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
                    null,
                ],
                'atLeastOneAvailableProduct' => true,
            ],
        ];
    }

    public function testViewSelectedShoppingListWithoutLineItemPrice(): void
    {
        // assert selected shopping list
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->initClient(
            [],
            self::generateBasicAuthHeader(BaseLoadCustomerData::AUTH_USER, BaseLoadCustomerData::AUTH_PW)
        );
        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_product.general_frontend_product_visibility',
            [
                ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_IN_STOCK
                ),
                ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_OUT_OF_STOCK
                ),
                ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_DISCONTINUED
                ),
            ]
        );
        $configManager->flush();

        $this->requestShoppingListPage('oro_shopping_list_frontend_view', $shoppingList->getId());

        $availableInventoryStatuses = [
            self::getContainer()->get('doctrine')->getRepository(EnumOption::class)
                ->find(ExtendHelper::buildEnumOptionId(
                    Product::INVENTORY_STATUS_ENUM_CODE,
                    Product::INVENTORY_STATUS_IN_STOCK
                ))
        ];

        $configManager = self::getConfigManager();
        $configManager->set('oro_rfp.frontend_product_visibility', $availableInventoryStatuses);
        $configManager->flush();

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, 200);

        $content = self::jsonToArray($response->getContent());
        $pageCrawler = new Crawler($content['page_content']);
        $buttonsCrawler = new Crawler($content['combined_button_wrapper']);

        self::assertStringContainsString($shoppingList->getLabel(), $pageCrawler->html());
        self::assertStringContainsString('Checkout', $buttonsCrawler->html());

        $response = $this->client->requestFrontendGrid(
            'frontend-customer-user-shopping-list-grid',
            ['frontend-customer-user-shopping-list-grid[shopping_list_id]' => $shoppingList->getId()],
            true
        );

        self::assertJsonResponseStatusCodeEquals($response, 200);

        $data = self::jsonToArray($response->getContent())['data'];

        self::assertCount(1, $data);
        self::assertNull($data[0]['price']);
    }

    public function testAssign(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(BaseLoadCustomerData::AUTH_USER, BaseLoadCustomerData::AUTH_PW)
        );

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);

        $parameters = ['id' => $shoppingList->getId(), '_widgetContainer' => 'dialog', '_wid' => uniqid('abc', true)];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_assign', $parameters)
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('shopping-list-assign-grid', $crawler->html());
    }

    public function testQuickAdd(): void
    {
        $this->markTestSkipped(
            'Waiting for new quick order page to be finished'
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_quick_add'));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $products = [[
            'productSku' => $product->getSku(),
            'productQuantity' => 15,
            'productUnit' => 'kg'
        ]];

        /** @var ShoppingList $currentShoppingList */
        $currentShoppingList = self::getContainer()->get('oro_shopping_list.manager.current_shopping_list')
            ->getForCurrentUser();

        $this->assertQuickAddFormSubmitted($crawler, $products);//add to current
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 15);
        $this->assertQuickAddFormSubmitted($crawler, $products, $currentShoppingList->getId());//add to specific
        $this->assertShoppingListItemSaved($currentShoppingList, $product->getSku(), 30);
    }

    /**
     * @group frontend-ACL
     * @dataProvider aclProvider
     */
    public function testACL(
        string $route,
        string $resource,
        string $user,
        int $status,
        bool $expectedCreateOrderButtonVisible
    ): void {
        $this->initClient([], self::generateBasicAuthHeader($user, $user));

        /* @var ShoppingList $resource */
        $resource = $this->getReference($resource);

        $this->requestShoppingListPage($route, $resource->getId());

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, $status);

        if (200 === $response->getStatusCode()) {
            $content = self::jsonToArray($response->getContent());
            if (empty($content['combined_button_wrapper'])) {
                return;
            }

            $buttonsCrawler = new Crawler($content['combined_button_wrapper']);

            if ($expectedCreateOrderButtonVisible) {
                self::assertStringContainsString('Checkout', $buttonsCrawler->html());
            } elseif ($buttonsCrawler->count()) {
                self::assertStringNotContainsString('Checkout', $buttonsCrawler->html());
            }
        }
    }

    public function aclProvider(): array
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

    public function testViewListForGuest(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_view'));
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('You do not have available Shopping Lists', $crawler->html());
    }

    public function testUpdateListForGuest(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_update'));
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('You do not have available Shopping Lists', $crawler->html());
    }

    private function assertQuickAddFormSubmitted(
        Crawler $crawler,
        array $products,
        ?int $shippingListId = null
    ): Crawler {
        $form = $crawler->filter('form[name="oro_product_quick_add"]')->form();

        $this->client->followRedirects();

        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'oro_product_quick_add' => [
                    '_token' => $form['oro_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => 'oro_shopping_list_quick_add_processor',
                    'additional' => $shippingListId
                ]
            ]
        );

        $expectedMessage = self::getContainer()
            ->get('translator')
            ->trans('oro.shoppinglist.actions.add_success_message', ['%count%' => count($products)]);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString($expectedMessage, $crawler->html());

        return $crawler;
    }

    private function assertShoppingListItemSaved(ShoppingList $shoppingList, string $sku, int $quantity): void
    {
        /** @var LineItem[] $items */
        $items = self::getContainer()->get('doctrine')
            ->getRepository(LineItem::class)
            ->findBy(['shoppingList' => $shoppingList], ['id' => 'DESC']);

        self::assertCount(2, $items);
        $item = $items[0];

        self::assertEquals($sku, $item->getProductSku());
        self::assertEquals($quantity, $item->getQuantity());
    }

    private function assertLineItemPriceEquals($expected, array $data): void
    {
        $expected = (array)$expected;
        self::assertSameSize($expected, $data);
        foreach ($data as $value) {
            self::assertContains($value['price'], $expected);
        }
    }

    private function requestShoppingListPage(string $route, int $id): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                ['id' => $id, 'layout_block_ids' => ['page_content', 'combined_button_wrapper']]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
