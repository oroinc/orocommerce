<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadAccountUserRoleACLData::class
            ]
        );
    }

    public function testDeletePredefinedRole()
    {
        $this->loginUser(LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL);
        $predefinedRole = $this->getReference(LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL);
        $this->assertNotNull($predefinedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_frontend_account_delete_accountuserrole', ['id' => $predefinedRole->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);

        $this->assertNotNull($this->getReference(LoadAccountUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL));
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testDeleteCustomizedRole($login, $resource, $status)
    {
        $this->loginUser($login);
        /** @var AccountUserRole $customizedRole */
        $customizedRole = $this->getReference($resource);
        $customizedRole1 = $this->getRepository()->findOneBy(['label' => $resource]);
        $this->assertNotNull($customizedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_frontend_account_delete_accountuserrole', ['id' => $customizedRole->getId()])
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, $status);
        if ($status === 204) {
            /** @var AccountUserRole $role */
            $role = $this->getRepository()->findOneBy(['label' => $resource]);
            $this->assertNull($role);
        }
    }

    /**
     * @return array
     */
    public function deleteDataProvider()
    {
        return [
            'anonymous user' => [
                'login' => '',
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => 401,
            ],
            'sibling user: LOCAL_VIEW_ONLY' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => 403,
            ],
            'parent account: LOCAL' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'parent account: DEEP_VIEW_ONLY' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'different account: DEEP' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'same account: LOCAL' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'status' => 204,
            ],
            'parent account: DEEP' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 204,
            ],
        ];
    }

    /**
     * @return ObjectRepository
     */
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(AccountUserRole::class);
    }
}
