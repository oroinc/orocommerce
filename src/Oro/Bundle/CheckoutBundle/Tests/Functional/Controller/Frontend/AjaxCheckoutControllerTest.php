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
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
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

    public function testGetTotalsActionNotFound()
    {
        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_totals', ['entityId' => 0]));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testGetTotalsAction()
    {
        $checkout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        $this->client->request(
            'GET',
            $this->getUrl('oro_checkout_frontend_totals', ['entityId' => $checkout->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('subtotals', $result);
    }
}
