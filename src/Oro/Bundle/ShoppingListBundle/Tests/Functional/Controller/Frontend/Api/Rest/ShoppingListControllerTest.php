<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
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
            $currentShoppingList = $this->getContainer()->get('oro_shopping_list.shopping_list.manager')
                ->getCurrent();

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
            'user from another customer' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from parent customer : DEEP_VIEW_ONLY' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 403,
            ],
            'EDIT (user from parent customer : LOCAL)' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 204,
            ],
            'user from same customer : LOCAL' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 204,
            ],
            'BASIC' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 204,
            ],
        ];
    }

    /**
     * @dataProvider ownerProvider
     * @param string $resource
     * @param string $user
     * @param int $status
     * @param string $assignedUserEmail
     */
    public function testSetOwner($resource, $user, $assignedUserEmail, $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);
        $assignedUser = $this->getReference($assignedUserEmail);

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_set_shoppinglist_owner', ['id' => $shoppingList->getId()]),
            ["ownerId" => $assignedUser->getId()]
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, $status);
    }

    /**
     * @return array
     */
    public function ownerProvider()
    {
        return [
            'anonymous user' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_LOCAL,
                'user' => '',
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_BASIC,
                'status' => 401,
            ],
            'user from another customer' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from parent customer : DEEP_VIEW_ONLY' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_2_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'assign to user from another customer : LOCAL' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_DEEP,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'assign to user in same customer : BASIC' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_BASIC,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_BASIC,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'assign to user in same customer : LOCAL' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_BASIC,
                'status' => 200,
            ],
            'assign to user in child customer : DEEP' => [
                'resource' => LoadShoppingListACLData::SHOPPING_LIST_ACC_1_2_USER_LOCAL,
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'assignedUserEmail' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_BASIC,
                'status' => 200,
            ],
        ];
    }
}
