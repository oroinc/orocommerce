<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

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
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );
    }

    public function testSetCurrent()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);

        $this->client->request(
            'PUT',
            $this->getUrl('orob2b_api_set_shoppinglist_current', ['id' => $shoppingList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $currentUser = $this->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('OroAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);

        $currentShoppingList = $this->getContainer()->get('doctrine')
            ->getRepository('OroShoppingListBundle:ShoppingList')
            ->findCurrentForAccountUser($currentUser);

        $this->assertEquals($currentShoppingList->getId(), $shoppingList->getId());
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
