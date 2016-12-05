<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_role_create'));
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

    /**
     * @group frontend-ACL
     * @dataProvider gridACLProvider
     *
     * @param string $user
     * @param string $indexResponseStatus
     * @param string $gridResponseStatus
     * @param array $data
     */
    public function testGridACL($user, $indexResponseStatus, $gridResponseStatus, array $data = [])
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_customer_frontend_account_user_role_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-account-account-user-roles-grid',
            ]
        );
        self::assertResponseStatusCodeEquals($response, $gridResponseStatus);
        if (200 === $gridResponseStatus) {
            $result = self::jsonToArray($response->getContent());
            $actual = array_column($result['data'], 'id');
            $actual = array_map('intval', $actual);
            $expected = array_map(
                function ($ref) {
                    return $this->getReference($ref)->getId();
                },
                $data
            );
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function gridACLProvider()
    {
        return [
            'NOT AUTHORISED' => [
                'user' => '',
                'indexResponseStatus' => 401,
                'gridResponseStatus' => 403,
                'data' => [],
            ],
            'DEEP: all siblings and children' => [
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserRoleACLData::ROLE_FRONTEND_ADMINISTRATOR,
                    LoadAccountUserRoleACLData::ROLE_FRONTEND_BUYER,
                    LoadAccountUserRoleACLData::ROLE_DEEP,
                    LoadAccountUserRoleACLData::ROLE_DEEP_VIEW_ONLY,
                    LoadAccountUserRoleACLData::ROLE_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_LOCAL_VIEW_ONLY,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL_CANT_DELETED,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP_CANT_DELETED,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL_CANT_DELETED,
                    LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL_CANT_DELETED
                ],
            ],
            'LOCAL: all siblings' => [
                'user' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_2_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadAccountUserRoleACLData::ROLE_FRONTEND_ADMINISTRATOR,
                    LoadAccountUserRoleACLData::ROLE_FRONTEND_BUYER,
                    LoadAccountUserRoleACLData::ROLE_DEEP,
                    LoadAccountUserRoleACLData::ROLE_DEEP_VIEW_ONLY,
                    LoadAccountUserRoleACLData::ROLE_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_LOCAL_VIEW_ONLY,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL,
                    LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL_CANT_DELETED,
                    LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL_CANT_DELETED
                ],
            ],
        ];
    }
}
