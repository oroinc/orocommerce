<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );
    }

    public function testDelete()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_shoppinglist', ['id' => $shoppingList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
