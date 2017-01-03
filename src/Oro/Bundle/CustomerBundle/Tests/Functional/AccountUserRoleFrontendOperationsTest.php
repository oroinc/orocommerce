<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountUserRoleFrontendOperationsTest extends WebTestCase
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

        $this->executeOperation($predefinedRole, 'oro_account_frontend_delete_role');

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);

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
        $this->assertNotNull($customizedRole);

        $this->executeOperation($customizedRole, 'oro_account_frontend_delete_role');

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, $status);
        if ($status === 200) {
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
                'status' => 404,
            ],
            'sibling user: LOCAL_VIEW_ONLY' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => 404,
            ],
            'parent account: LOCAL' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 404,
            ],
            'parent account: DEEP_VIEW_ONLY' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 404,
            ],
            'different account: DEEP' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 404,
            ],
            'same account: LOCAL' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'status' => 200,
            ],
            'parent account: DEEP' => [
                'login' => LoadAccountUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 200,
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

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(AccountUserRole $accountUserRole, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_frontend_account_user_role_view',
                    'entityId' => $accountUserRole->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\AccountUserRole'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
