<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;

class AjaxCheckoutControllerTest extends FrontendWebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadCustomerUserData::class,
                LoadCustomerAddresses::class,
                LoadProductUnitPrecisions::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentTermData::class,
                LoadShippingMethodsConfigsRulesWithConfigs::class,
                LoadShoppingListsCheckoutsData::class,
            ]
        );
    }

    public function testGetTotalsActionNotFound(): void
    {
        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_totals', ['entityId' => 0]));
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testGetTotalsAction(): void
    {
        $checkout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7);

        $this->client->request(
            'GET',
            $this->getUrl('oro_checkout_frontend_totals', ['entityId' => $checkout->getId()])
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);
        self::assertArrayHasKey('total', $result);
        self::assertArrayHasKey('subtotals', $result);
    }

    public function testGetTotalsActionOfAnotherCustomerUser(): void
    {
        $checkout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        $this->client->request(
            'GET',
            $this->getUrl('oro_checkout_frontend_totals', ['entityId' => $checkout->getId()])
        );
        $result = $this->client->getResponse();
        self::assertEquals(403, $result->getStatusCode());
    }
}
