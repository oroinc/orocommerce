<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CustomerUserShoppingListsProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
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

    public function testGetCurrent(): void
    {
        $this->loginUser(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $shoppingList = $this->getContainer()
            ->get('oro_shopping_list.layout.data_provider.customer_user_shopping_lists')
            ->getCurrent();

        $expectedShoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP);

        $this->assertNotNull($shoppingList);
        $this->assertEquals($expectedShoppingList->getId(), $shoppingList->getId());
    }

    public function testIsCurrent(): void
    {
        $this->loginUser(LoadShoppingListUserACLData::USER_ACCOUNT_1_ROLE_DEEP);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $provider = $this->getContainer()
            ->get('oro_shopping_list.layout.data_provider.customer_user_shopping_lists');

        $this->assertTrue(
            $provider->isCurrent($this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_DEEP))
        );
        $this->assertFalse(
            $provider->isCurrent($this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL))
        );
    }

    /**
     * @dataProvider ACLProvider
     */
    public function testGetShoppingLists(array $shoppingLists, string $user): void
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $actualShoppingLists = $this->getContainer()
            ->get('oro_shopping_list.layout.data_provider.customer_user_shopping_lists')
            ->getShoppingLists();

        $actual = [];
        foreach ($actualShoppingLists as $shoppingList) {
            $actual[] = $shoppingList->getLabel();
        }
        sort($actual);
        $this->assertEquals($shoppingLists, $actual);
    }

    public function ACLProvider(): array
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

    /**
     * @dataProvider getShoppingListsForWidgetWhenNotShowAllProvider
     */
    public function testGetShoppingListsForWidgetWhenNotShowAll(array $shoppingLists, string $user): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_shopping_list.show_all_in_shopping_list_widget', false);
        $configManager->flush();

        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $actualShoppingLists = $this->getContainer()
            ->get('oro_shopping_list.layout.data_provider.customer_user_shopping_lists')
            ->getShoppingListsForWidget();

        $actual = [];
        foreach ($actualShoppingLists as $shoppingList) {
            $actual[] = $shoppingList->getLabel();
        }
        sort($actual);
        $this->assertEquals($shoppingLists, $actual);
    }

    public function getShoppingListsForWidgetWhenNotShowAllProvider(): array
    {
        return [
            'VIEW (anonymous user)' => [
                'shoppingLists' => [],
                'user' => '',
            ],
            'VIEW (user from parent customer : DEEP_VIEW_ONLY)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_DEEP,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_DEEP,
            ],
            'VIEW (user from parent customer : LOCAL)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_LOCAL,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
            ],
            'VIEW (user from same customer : BASIC)' => [
                'shoppingLists' => [
                    LoadShoppingListACLData::SHOPPING_LIST_ACC_2_USER_BASIC,
                ],
                'user' => LoadShoppingListUserACLData::USER_ACCOUNT_2_ROLE_BASIC,
            ],
        ];
    }
}
