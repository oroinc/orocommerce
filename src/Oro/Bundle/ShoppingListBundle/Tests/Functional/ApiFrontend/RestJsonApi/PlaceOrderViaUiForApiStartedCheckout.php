<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class PlaceOrderViaUiForApiStartedCheckout extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroShoppingListBundle/Tests/Functional/ApiFrontend/DataFixtures/shopping_list.yml',
        ]);
    }

    public function testCanPlaceOrderViaUiForApiStartedCheckout(): void
    {
        // Start Checkout with API from Shopping List
        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout']
        );

        $checkoutId = $this->getResourceId($response);

        // Init UI access for same buyer
        $customerUser = $this->getReference('customer_user');
        $this->initClient(
            [],
            self::generateBasicAuthHeader($customerUser->getEmail(), 'test')
        );

        // Check that Checkout workflow is available for API-started checkout entity.
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_checkout_frontend_checkout', ['id' => $checkoutId])
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString('Billing Information', $crawler->html());
    }
}
