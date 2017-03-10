<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional;

use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadBaseCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;

class ShoppingListFrontendActionsTest extends FrontendActionTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadBaseCustomerUserData::AUTH_USER, LoadBaseCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentMethodsConfigsRuleData::class
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $data = $this->startCheckout($shoppingList);

        $crawler = $this->client->request('GET', $data['workflowItem']['result']['redirectUrl']);

        $content = $crawler->filter('.checkout-order-summary')->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }

    public function testCreateOrdersFromSingleShoppingList()
    {
        // start checkout from first user
        $this->authUser(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD);
        $firstData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));
        // continue checkout from first user
        $secondData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));

        $this->assertEquals($firstData['workflowItem']['id'], $secondData['workflowItem']['id']);

        // start checkout from second user
        $this->authUser(LoadCustomerUserData::LEVEL_1_EMAIL, LoadCustomerUserData::LEVEL_1_PASSWORD);
        $startData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));

        $this->assertNotEquals($firstData['workflowItem']['id'], $startData['workflowItem']['id']);
    }

    /**
     * @param string $username
     * @param string $password
     */
    protected function authUser($username, $password)
    {
        $this->initClient([], $this->generateBasicAuthHeader($username, $password));
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function startCheckout(ShoppingList $shoppingList)
    {
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        $link = $crawler->selectLink('Create Order');
        $this->assertCount(2, $link);
        $this->assertNotEmpty($link->attr('data-transition-url'));
        $this->client->request('GET', $link->attr('data-transition-url'));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('workflowItem', $data);
        $this->assertArrayHasKey('result', $data['workflowItem']);
        $this->assertArrayHasKey('redirectUrl', $data['workflowItem']['result']);

        return $data;
    }
}
