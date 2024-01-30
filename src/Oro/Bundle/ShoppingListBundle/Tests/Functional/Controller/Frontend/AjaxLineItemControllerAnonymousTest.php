<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxLineItemControllerAnonymousTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadCombinedProductPrices::class]);
    }

    public function testAddProductFromViewNotValidShoppingList()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');

        // Pre-check that guest shopping lists disabled.
        // In this case null will be returned by CurrentShoppingListManager.
        $this->assertFalse(
            $this->getContainer()->get('oro_shopping_list.manager.guest_shopping_list')->isGuestShoppingListAvailable()
        );

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_add_product',
                [
                    'productId' => $product->getId(),
                    'shoppingListId' => 1,
                ]
            ),
            [
                'oro_product_frontend_line_item' => [
                    'quantity' => 1,
                    'unit' => $product->getPrimaryUnitPrecision()->getUnit()->getCode(),
                    '_token' => $this->getCsrfToken('oro_product_frontend_line_item')->getValue()
                ],
            ]
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }
}
