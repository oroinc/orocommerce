<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Datagrid;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCompletedAndNonCompletedSimpleCheckoutsData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutGridTest extends FrontendWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures([
            LoadCompletedAndNonCompletedSimpleCheckoutsData::class,
        ]);
    }

    public function testThatNoCompletedCheckoutsInGridByDefault()
    {
        $this->client->request('GET', '/');
        $gridResponse = $this->client->requestFrontendGrid(['gridName' => 'frontend-checkouts-grid']);

        $parsedData = json_decode($gridResponse->getContent(), true)['data'];
        $this->assertCount(1, $parsedData);
        $checkoutData = reset($parsedData);

        $checkout = $this->getReference(LoadCompletedAndNonCompletedSimpleCheckoutsData::CHECKOUT_NOT_COMPLETED);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $checkout->getSourceEntity();

        static::assertStringContainsString($shoppingList->getLabel(), $checkoutData['startedFrom']);
    }
}
