<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
                LoadShoppingListACLData::class,
            ]
        );
    }

    /**
     * @dataProvider ACLProvider
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testSetCurrent($resource, $user, $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_set_shoppinglist_current', ['id' => $shoppingList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, $status);
        if ($user && $status == 204) {
            $currentUser = $this->getReference($user);

            $currentShoppingList = $this->getContainer()->get('doctrine')
                ->getRepository('OroShoppingListBundle:ShoppingList')
                ->findCurrentForAccountUser($currentUser);

            $this->assertEquals($currentShoppingList->getId(), $shoppingList->getId());
        }
    }

    public function testSetCurrentFailsOnNonExistingList()
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_set_shoppinglist_current', ['id' => -1])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @dataProvider ACLProvider
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testDelete($resource, $user, $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_shoppinglist', ['id' => $shoppingList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'anonymous user' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => '',
                'status' => 401,
            ],
            'user from another account' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from parent account : DEEP_VIEW_ONLY' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
            //TODO: uncomment in scope BB-5234
//            'EDIT (user from parent account : LOCAL)' => [
//                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
//                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
//                'status' => 200,
//            ],
//            'user from same account : LOCAL' => [
//                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
//                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
//                'status' => 204,
//            ],
            'BASIC' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 204,
            ],
        ];
    }
}
