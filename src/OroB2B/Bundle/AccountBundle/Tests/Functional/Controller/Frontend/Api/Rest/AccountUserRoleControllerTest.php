<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

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
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testDeletePredefinedRole()
    {
        $predefinedRole = $this->getRoleByLabel(LoadAccountUserRoleData::ROLE_WITHOUT_ACCOUNT);
        $this->assertNotNull($predefinedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_frontend_account_delete_accountuserrole', ['id' => $predefinedRole->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);

        $this->assertNotNull($this->getRoleByLabel(LoadAccountUserRoleData::ROLE_WITHOUT_ACCOUNT));
    }

    public function testDeleteCustomizedRole()
    {
        $currentUser = $this->getCurrentUser();
        $currentUser->setAccount($this->getReference('account.orphan'));
        $this->getObjectManager()->flush();


        /** @var AccountUserRole $customizedRole */
        $customizedRole = $this->getRoleByLabel(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT);
        $this->assertNotNull($customizedRole);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_frontend_account_delete_accountuserrole', ['id' => $customizedRole->getId()])
        );

        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
        $this->assertNull($this->getRoleByLabel(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT));
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
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUserRole');
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUser');
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

    /**
     * @return AccountUser
     */
    protected function getCurrentUser()
    {
        return $this->getUserRepository()->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }
}
