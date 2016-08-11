<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses;
use OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;

/**
 * @dbIsolation
 */
class AjaxCheckoutControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );
        $this->loadFixtures(
            [
                LoadAccountUserData::class,
                LoadAccountAddresses::class,
                LoadProductUnitPrecisions::class,
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentTermData::class,
                LoadShippingRules::class,
                LoadShoppingListsCheckoutsData::class,
            ]
        );
    }

    public function testGetTotalsActionNotFound()
    {
        $this->client->request('GET', $this->getUrl('orob2b_checkout_frontend_totals', ['entityId' => 0]));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testGetTotalsAction()
    {
        $checkout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_checkout_frontend_totals', ['entityId' => $checkout->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('subtotals', $result);
    }
}
