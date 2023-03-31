<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductKitCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxProductKitLineItemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadProductKitData::class,
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
                'success' => false,
                'messages'=> [
                    'error' => [
                        'This product is not a product kit.'
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

        self::assertTrue($result['success']);
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

        self::assertTrue($result['success']);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $result['subtotal']['type']);
        self::assertEquals('USD', $result['subtotal']['currency']);
        self::assertEquals(100, $result['subtotal']['amount']);
    }
}
