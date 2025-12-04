<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ControllerFrontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductKitCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListProductKitLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class AjaxProductKitLineItemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadShoppingListProductKitLineItems::class,
            LoadProductKitCombinedProductPrices::class,
        ]);
    }

    public function testCreateNotProductKit(): void
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');

        $this->ajaxRequest(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_create',
                [
                    'productId' => $product->getId(),
                ]
            )
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 400);

        self::assertEquals(
            [
                'successful' => false,
                'messages' => [
                    'error' => [
                        'This product is not a product kit.',
                    ],
                ],
            ],
            $result
        );
    }

    public function testCreateOpenConfiguration(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        $this->ajaxRequest(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_create',
                [
                    'productId' => $product->getId(),
                ]
            ),
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testGetSubtotalNotConfiguredProductKit(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_create',
                [
                    'productId' => $productKit->getId(),
                ]
            ),
            [
                'getSubtotal' => true,
            ]
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertTrue($result['successful']);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $result['subtotal']['type']);
        self::assertEquals('USD', $result['subtotal']['currency']);
        self::assertEquals(30, $result['subtotal']['amount']);
    }

    public function testGetSubtotal(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $kitItem1Product */
        $kitItem1Product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_create',
                [
                    'productId' => $productKit->getId(),
                ]
            ),
            [
                'getSubtotal' => true,
                'oro_product_kit_line_item' => [
                    'unit' => 'milliliter',
                    'quantity' => 2,
                    'kitItemLineItems' => [
                        [
                            'quantity' => 3,
                            'product' => $kitItem1Product->getId(),
                        ],
                    ],
                ],
            ]
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertTrue($result['successful']);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $result['subtotal']['type']);
        self::assertEquals('USD', $result['subtotal']['currency']);
        self::assertEquals(100, $result['subtotal']['amount']);
    }

    public function testCreateProductKitLineItem(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKit->getKitItems()->first();

        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);
        self::assertCount(0, $shoppingList->getLineItems());

        $productKitLineItemQuantity = 1;
        $productKitLineItemUnit = $productKit->getPrimaryUnitPrecision()->getProductUnitCode();
        $productKitItemLineItems = [
            [
                'product' => $productKitItem->getProducts()->first()?->getId(),
                'quantity' => 1,
            ],
        ];

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_create',
                [
                    'productId' => $productKit->getId(),
                ]
            ),
            [
                'oro_product_kit_line_item' => [
                    'quantity' => $productKitLineItemQuantity,
                    'unit' => $productKitLineItemUnit,
                    'kitItemLineItems' => $productKitItemLineItems,
                    '_token' => $this->getCsrfToken('submit')->getValue(),
                ],
            ]
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $link = self::getContainer()->get(ShoppingListUrlProvider::class)->getFrontendUrl($shoppingList);
        $label = htmlspecialchars($shoppingList->getLabel());

        $message = self::getContainer()->get(TranslatorInterface::class)->trans(
            'oro.frontend.shoppinglist.product_kit_line_item.added_to_shopping_list',
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );

        self::assertEquals(
            [
                'successful' => true,
                'message' => $message,
                'product' => [
                    'id' => $productKit->getId(),
                    'shopping_lists' => self::getContainer()
                        ->get(ProductShoppingListsDataProvider::class)
                        ->getProductUnitsQuantity($productKit->getId()),
                ],
                'shoppingList' => [
                    'id' => $shoppingList->getId(),
                    'label' => $shoppingList->getLabel(),
                ],
            ],
            $result
        );
        self::assertCount(1, $shoppingList->getLineItems());
        $this->assertProductKitLineItem(
            $shoppingList->getLineItems()->first(),
            $productKitLineItemQuantity,
            $productKitLineItemUnit,
            $productKitItemLineItems
        );
    }

    public function testUpdateOpenConfiguration(): void
    {
        /** @var LineItem $productKitLineItem */
        $productKitLineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);

        $this->ajaxRequest(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_update',
                [
                    'id' => $productKitLineItem->getId(),
                ]
            ),
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testUpdateProductKitLineItem(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKit->getKitItems()->first();

        /** @var LineItem $productKitLineItem */
        $productKitLineItem = $this->getReference(LoadShoppingListProductKitLineItems::LINE_ITEM_1);

        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        self::assertCount(1, $shoppingList->getLineItems());
        $this->assertProductKitLineItem(
            $shoppingList->getLineItems()->first(),
            $productKitLineItem->getQuantity(),
            $productKitLineItem->getProductUnitCode(),
            [
                [
                    'product' => $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                    'quantity' => 11,
                ],
            ]
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_product_kit_line_item_update',
                [
                    'id' => $productKitLineItem->getId(),
                ]
            ),
            [
                'oro_product_kit_line_item' => [
                    'quantity' => 10,
                    'unit' => $productKit->getPrimaryUnitPrecision()->getProductUnitCode(),
                    'kitItemLineItems' => [
                        [
                            'product' => $productKitItem->getProducts()->first()?->getId(),
                            'quantity' => 10,
                        ],
                    ],
                    '_token' => $this->getCsrfToken('submit')->getValue(),
                ],
            ]
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $link = self::getContainer()->get(ShoppingListUrlProvider::class)->getFrontendUrl($shoppingList);
        $label = htmlspecialchars($shoppingList->getLabel());

        $message = self::getContainer()->get(TranslatorInterface::class)->trans(
            'oro.frontend.shoppinglist.product_kit_line_item.updated_in_shopping_list',
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );

        self::assertEquals(
            [
                'successful' => true,
                'message' => $message,
                'product' => [
                    'id' => $productKit->getId(),
                    'shopping_lists' => self::getContainer()
                        ->get(ProductShoppingListsDataProvider::class)
                        ->getProductUnitsQuantity($productKit->getId()),
                ],
                'shoppingList' => [
                    'id' => $shoppingList->getId(),
                    'label' => $shoppingList->getLabel(),
                ],
            ],
            $result
        );
        self::assertCount(1, $shoppingList->getLineItems());
        $this->assertProductKitLineItem(
            $shoppingList->getLineItems()->first(),
            10,
            $productKitLineItem->getProductUnitCode(),
            [
                [
                    'product' => $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                    'quantity' => 10,
                ],
            ]
        );
    }

    private function assertProductKitLineItem(
        LineItem $lineItem,
        float $expectedQuantity,
        string $expectedUnitCode,
        array $expectedKitItemLineItems
    ): void {
        self::assertEquals($expectedQuantity, $lineItem->getQuantity());
        self::assertEquals($expectedUnitCode, $lineItem->getUnit()->getCode());

        $kitItemLineItems = $lineItem->getKitItemLineItems();
        self::assertCount(count($expectedKitItemLineItems), $kitItemLineItems);
        foreach ($expectedKitItemLineItems as $index => $expectedKitItemLineItem) {
            $kitItemLineItem = $lineItem->getKitItemLineItems()->get($index);

            self::assertEquals($expectedKitItemLineItem['product'], $kitItemLineItem->getProduct()?->getId());
            self::assertEquals($expectedKitItemLineItem['quantity'], $kitItemLineItem->getQuantity());
        }
    }
}
