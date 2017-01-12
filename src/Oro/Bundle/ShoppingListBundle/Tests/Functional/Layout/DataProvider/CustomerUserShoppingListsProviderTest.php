<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CustomerUserShoppingListsProviderTest extends WebTestCase
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
     * @param array $shoppingLists
     * @param string $user
     */
    public function testGetShoppingLists($shoppingLists, $user)
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $actualShoppingLists = self::$clientInstance->getContainer()
            ->get('oro_shopping_list.layout.data_provider.customer_user_shopping_lists')
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
            'VIEW (user from parent customer : DEEP_VIEW_ONLY)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_2_USER_LOCAL,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
            ],
            'VIEW (user from parent customer : LOCAL)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP,
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
            ],
            'VIEW (user from same customer : LOCAL)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
            ],
            'VIEW (user from same customer : BASIC)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_BASIC,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
            ],
        ];
    }
}
