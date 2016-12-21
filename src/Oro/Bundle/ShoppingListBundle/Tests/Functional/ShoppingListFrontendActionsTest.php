<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as LoadBaseAccountUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;


/**
 * @dbIsolation
 */
class ShoppingListFrontendActionsTest extends FrontendActionTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadBaseAccountUserData::AUTH_USER, LoadBaseAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
    }

    public function testCreateOrder()
    {
        if (!$this->client->getContainer()->hasParameter('oro_checkout.entity.checkout.class')) {
            $this->markTestSkipped('CheckoutBundle disabled');
        }

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
        if (!$this->client->getContainer()->hasParameter('oro_checkout.entity.checkout.class')) {
            $this->markTestSkipped('CheckoutBundle disabled');
        }

        // start checkout from first user
        $this->authUser(LoadAccountUserData::LEVEL_1_1_EMAIL, LoadAccountUserData::LEVEL_1_1_PASSWORD);
        $firstData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));
        // continue checkout from first user
        $secondData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));

        $this->assertEquals($firstData['workflowItem']['id'], $secondData['workflowItem']['id']);

        // start checkout from second user
        $this->authUser(LoadAccountUserData::LEVEL_1_EMAIL, LoadAccountUserData::LEVEL_1_PASSWORD);
        $startData = $this->startCheckout($this->getReference(LoadShoppingLists::SHOPPING_LIST_7));

        $this->assertNotEquals($firstData['workflowItem']['id'], $startData['workflowItem']['id']);
    }

    public function testCreateRequest()
    {
        if (!$this->client->getContainer()->hasParameter('oro_rfp.entity.request.class')) {
            $this->markTestSkipped('RFPBundle disabled');
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeOperation($shoppingList, 'oro_shoppinglist_frontend_request_quote');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);

        $this->assertTrue($data['success']);

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $lineItems = $crawler->filter('.request-form-editline__product');
        $this->assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
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

        //print_r(['rc' => $this->client->getResponse()->getContent()]);

        $link = $crawler->selectLink('Create Order')->link();

        $this->client->click($link);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('workflowItem', $data);
        $this->assertArrayHasKey('result', $data['workflowItem']);
        $this->assertArrayHasKey('redirectUrl', $data['workflowItem']['result']);

        return $data;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $operationName
     */
    protected function executeOperation(ShoppingList $shoppingList, $operationName)
    {
        $this->assertExecuteOperation(
            $operationName,
            $shoppingList->getId(),
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['route' => 'oro_shopping_list_frontend_view']
        );
    }
}
