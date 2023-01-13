<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadBaseCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingMethodsConfigsRulesWithConfigs;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;

class ShoppingListFrontendActionsTest extends FrontendActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadBaseCustomerUserData::AUTH_USER, LoadBaseCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
                LoadPaymentMethodsConfigsRuleData::class,
                LoadShippingMethodsConfigsRulesWithConfigs::class,
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $data = $this->startCheckout($shoppingList);

        $crawler = $this->client->request('GET', $data['workflowItem']['result']['redirectUrl']);

        $content = $crawler->filter('.totals-container table tr td')->html();
        static::assertStringContainsString(sprintf('%s Item', count($shoppingList->getLineItems())), $content);
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

    private function authUser(string $username, string $password): void
    {
        $this->initClient([], $this->generateBasicAuthHeader($username, $password));
    }

    private function startCheckout(ShoppingList $shoppingList): array
    {
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_view',
                ['id' => $shoppingList->getId(), 'layout_block_ids' => ['combined_button_wrapper']]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $content = self::jsonToArray($response->getContent());
        $crawler = new Crawler($content['combined_button_wrapper']);

        $link = $crawler->selectLink('Create Order');
        $this->assertCount(1, $link);
        $this->assertNotEmpty($link->attr('data-transition-url'));
        $this->ajaxRequest('POST', $link->attr('data-transition-url'));

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $data = self::jsonToArray($response->getContent());

        $this->assertArrayHasKey('workflowItem', $data);
        $this->assertArrayHasKey('result', $data['workflowItem']);
        $this->assertArrayHasKey('redirectUrl', $data['workflowItem']['result']);

        return $data;
    }
}
