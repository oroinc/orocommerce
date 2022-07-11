<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListControllerTest extends WebTestCase
{
    use OperationAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadShoppingListACLData::class]);
    }

    /**
     * @dataProvider ACLProvider
     */
    public function testSetCurrent(string $resource, string $user, int $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_set_shopping_list_current', ['id' => $shoppingList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, $status);
        if ($user && $status === 204) {
            $currentShoppingList = $this->getContainer()->get('oro_shopping_list.manager.current_shopping_list')
                ->getCurrent();

            $this->assertEquals($currentShoppingList->getId(), $shoppingList->getId());
        }
    }

    public function testSetCurrentFailsOnNonExistingList(): void
    {
        $url = str_replace($id = 999999, 'invalid', $this->getUrl('oro_api_set_shopping_list_current', ['id' => $id]));
        $this->client->jsonRequest('PUT', $url);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @dataProvider actionACLProvider
     */
    public function testDelete(string $resource, string $user, int $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);

        $operationName = 'oro_shoppinglist_delete';
        $entityId = $shoppingList->getId();
        $entityClass = ShoppingList::class;
        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass),
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), $status);

        if ($status === 200) {
            self::getContainer()->get('doctrine')->getManagerForClass(ShoppingList::class)->clear();

            $removedShoppingList = self::getContainer()->get('doctrine')
                ->getRepository(ShoppingList::class)
                ->find($entityId);

            self::assertNull($removedShoppingList);
        }
    }

    public function ACLProvider(): array
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

    public function actionACLProvider(): array
    {
        $acls = $this->ACLProvider();
        $acls['anonymous user']['status'] = 403;
        $acls['BASIC']['status'] = 200;
        $acls['EDIT (user from parent customer : LOCAL)']['status'] = 200;
        $acls['user from same customer : LOCAL']['status'] = 200;

        return $acls;
    }

    /**
     * @dataProvider ownerProvider
     */
    public function testSetOwner(string $resource, string $user, string $assignedUserEmail, int $status)
    {
        $this->loginUser($user);
        $shoppingList = $this->getReference($resource);
        $assignedUser = $this->getReference($assignedUserEmail);

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_set_shopping_list_owner', ['id' => $shoppingList->getId()]),
            ['ownerId' => $assignedUser->getId()]
        );
        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, $status);
    }

    public function ownerProvider(): array
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

    public function testSetOwnerWhenInvalidShoppingListId()
    {
        $url = str_replace($id = 999999, 'invalid', $this->getUrl('oro_api_set_shopping_list_owner', ['id' => $id]));
        $this->client->jsonRequest('PUT', $url);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
