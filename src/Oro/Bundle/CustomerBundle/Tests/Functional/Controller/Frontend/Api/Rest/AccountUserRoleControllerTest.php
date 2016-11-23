<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

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
        $predefinedRole = $this->getRoleByLabel(LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL);
        $this->assertNotNull($predefinedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_frontend_account_delete_accountuserrole', ['id' => $predefinedRole->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);

        $this->assertNotNull($this->getRoleByLabel(LoadAccountUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL));
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
        $customizedRole = $this->getRoleByLabel($resource);
        $this->assertNotNull($customizedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_frontend_account_delete_accountuserrole', ['id' => $customizedRole->getId()])
        );

        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, $status);
        if ($status === 204) {
            $this->assertNull($this->getRoleByLabel($resource));
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
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUserRole');
    }

    /**
     * @param string $label
     * @return AccountUserRole
     */
    protected function getRoleByLabel($label)
    {
        return $this->getUserRoleRepository()
            ->findOneBy(['label' => $label]);
    }
}
