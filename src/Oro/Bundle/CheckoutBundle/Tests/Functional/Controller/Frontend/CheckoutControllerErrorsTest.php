<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class CheckoutControllerErrorsTest extends CheckoutControllerTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW),
            true
        );
        $this->loadFixtures([
            LoadCustomerAddresses::class,
            LoadProductUnitPrecisions::class,
            LoadShoppingListLineItems::class,
            LoadCombinedProductPrices::class,
        ], true);
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testStartCheckoutProductsWithoutPrices()
    {
        $translator = $this->getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $this->startCheckout($shoppingList);
        $this->assertNull(self::$checkoutUrl);

        $flashBag = $this->getContainer()->get('session.flash_bag');
        $noItemsWithPriceError = $translator
            ->trans('oro.frontend.shoppinglist.messages.cannot_create_order_no_line_item_with_price');
        $this->assertTrue($flashBag->has('error'));
        $this->assertContains($noItemsWithPriceError, $flashBag->get('error'));
    }

    public function testStartCheckoutSeveralProductsWithoutPrices()
    {
        $translator = $this->getContainer()->get('translator');
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator
            ->trans('oro.checkout.order.line_items.line_item_has_no_price.message');
        $this->assertContains($noProductsError, $crawler->html());

        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $data = $this->setFormData($values, self::BILLING_ADDRESS);
        $crawler = $this->client->request('POST', $form->getUri(), $data);
        $this->assertContains($noProductsError, $crawler->html());

        $productId = $this->getReference(LoadProductData::PRODUCT_5)->getId();
        $url = $this->getUrl('oro_shopping_list_frontend_remove_product', [
            'productId' => $productId,
            'shoppingListId' => $shoppingList->getId(),
        ]);
        $this->client->request('POST', $url);
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $response = json_decode($result->getContent(), true);
        $this->assertTrue($response['successful']);

        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $noProductsError = $translator->trans('oro.checkout.workflow.condition.order_line_item_has_count.message');
        $this->assertContains($noProductsError, $crawler->html());
    }
}
