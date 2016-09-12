<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingRules;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;

/**
 * @dbIsolation
 */
class AjaxCheckoutControllerTest extends FrontendWebTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );
        $this->setCurrentWebsite('default');
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
