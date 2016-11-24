<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountUserShoppingListsProviderTest extends WebTestCase
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
     * @param array $shoppingLists
     * @param string $user
     */
    public function testGetShoppingLists($shoppingLists, $user)
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $actualShoppingLists = self::$clientInstance->getContainer()
            ->get('oro_shopping_list.layout.data_provider.account_user_shopping_lists')
            ->getShoppingLists();

        $actual = [];
        foreach ($actualShoppingLists as $shoppingList) {
            $actual[] = $shoppingList->getLabel();
        }
        sort($actual);
        $this->assertEquals($shoppingLists, $actual);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'VIEW (anonymous user)' => [
                'shoppingLists' => [],
                'user' => '',
            ],
            'VIEW (user from another account)' => [
                'shoppingLists' => [],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_BASIC,
            ],
            'VIEW (user from parent account : DEEP_VIEW_ONLY)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
            ],
            'VIEW (user from parent account : LOCAL)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
            ],
            'VIEW (user from same account : LOCAL)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
            ],
            'VIEW (user from same account : BASIC)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
            ],
        ];
    }
}
