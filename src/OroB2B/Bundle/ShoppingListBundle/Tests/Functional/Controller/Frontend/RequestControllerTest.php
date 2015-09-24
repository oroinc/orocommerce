<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            static::generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        if (!$this->client->getContainer()->hasParameter('orob2b_rfp.entity.request.class')) {
            static::markTestSkipped('RFPBundle disabled');
        }

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testCreateRequest()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        static::assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_shoppinglist_frontend_createrequest', ['id' => $shoppingList->getId()])
        );

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 302);

        $crawler = $this->client->followRedirect();
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringStartsWith(
            $this->getUrl('orob2b_rfp_frontend_request_create'),
            $this->client->getRequest()->getRequestUri()
        );
        static::assertEquals(true, $this->client->getRequest()->get(ProductDataStorage::STORAGE_KEY));

        $lineItems = $crawler->filter('[data-ftid=orob2b_rfp_frontend_request_type_requestProducts]');
        static::assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            static::assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }
}
