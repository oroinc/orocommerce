<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerACLTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadAccountUserRoleACLData::class
            ]
        );
    }

    public function testCreatePermissionDenied()
    {
        $this->loginUser(LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);
        $this->client->request('GET', $this->getUrl(
            'oro_customer_frontend_account_user_role_create'
        ));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 403);
    }

    /**
     * @param $route
     * @param $role
     * @param $user
     * @param $expectedStatus
     *
     * @dataProvider viewProvider
     */
    public function testACL($route, $role, $user, $expectedStatus)
    {
        $this->loginUser($user);
        /* @var $role AccountUserRole */
        $role = $this->getReference($role);
        $this->client->request('GET', $this->getUrl(
            $route,
            ['id' => $role->getId()]
        ));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, $expectedStatus);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function viewProvider()
    {
        return [
            'VIEW (user from parent account : DEEP)' => [
                'route' => 'oro_customer_frontend_account_user_role_view',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'expectedStatus' => 200
            ],
            'VIEW (user from another account)' => [
                'route' => 'oro_customer_frontend_account_user_role_view',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'expectedStatus' => 403
            ],
            'VIEW (anonymous user)' => [
                'route' => 'oro_customer_frontend_account_user_role_view',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'user' => '',
                'expectedStatus' => 401
            ],
            'VIEW (user from same account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_role_view',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'expectedStatus' => 200
            ],
            'UPDATE (user from parent account : DEEP)' => [
                'route' => 'oro_customer_frontend_account_user_role_update',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'expectedStatus' => 200
            ],
            'UPDATE (user from another account)' => [
                'route' => 'oro_customer_frontend_account_user_role_update',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'expectedStatus' => 403
            ],
            'UPDATE (anonymous user)' => [
                'route' => 'oro_customer_frontend_account_user_role_update',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'user' => '',
                'expectedStatus' => 401
            ],
            'UPDATE (user from same account : LOCAL)' => [
                'route' => 'oro_customer_frontend_account_user_role_update',
                'role' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'expectedStatus' => 200
            ],
        ];
    }
}
