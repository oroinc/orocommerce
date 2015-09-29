<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData as OroLoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    const PREDEFINED_ROLE = 'Test predefined role';
    const CUSTOMIZED_ROLE = 'Test customized role';
    const ACCOUNT_ROLE = 'Test account user role';
    const ACCOUNT_UPDATED_ROLE = 'Updated test account user role';

    /**
     * @var AccountUser
     */
    protected $currentUser;

    /**
     * @var AccountUserRole
     */
    protected $predefinedRole;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(OroLoadAccountUserData::AUTH_USER, OroLoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );

        $this->currentUser = $this->getCurrentUser();
        $this->predefinedRole = $this->getPredefinedRole();
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_role_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_frontend_account_user_role[label]'] = self::ACCOUNT_ROLE;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Account User Role has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_role_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $accountUserRoleLabel = $this->getReference(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER);
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-roles-grid',
            [
                'frontend-account-account-user-roles-grid[_filter][label][value]' => $accountUserRoleLabel
            ]
        );

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertContains(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER, $response->getContent());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-roles-grid',
            [
                'frontend-account-account-user-roles-grid[_filter][label][value]' => self::ACCOUNT_ROLE
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('orob2b_account_frontend_account_user_role')->getValue();
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'input_action'        => '',
            'orob2b_account_frontend_account_user_role' => [
                '_token' => $token,
                'label' => self::ACCOUNT_UPDATED_ROLE,
                'appendUsers' => $this->currentUser->getId(),
            ]
        ]);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $crawler->html();
        $this->assertContains('Account User Role has been saved', $content);

        $this->getObjectManager()->clear();

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->find($id);

        $this->assertNotNull($role);
        $this->assertEquals(self::ACCOUNT_UPDATED_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getCurrentUser();

        $this->assertEquals($user->getRole($role->getRole()), $role);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param $id
     */
    public function testView($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_view', ['id' => $id])
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        $response = $this->requestFrontendGrid(
            'frontend-account-account-users-grid-view',
            [
                'frontend-account-account-users-grid-view[role]' => $id,
                'frontend-account-account-users-grid-view[_filter][email][value]' => OroLoadAccountUserData::AUTH_USER
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);
        $result = reset($result['data']);

        $this->assertEquals($this->currentUser->getId(), $result['id']);
        $this->assertEquals($this->currentUser->getFirstName(), $result['firstName']);
        $this->assertEquals($this->currentUser->getLastName(), $result['lastName']);
        $this->assertEquals($this->currentUser->getEmail(), $result['email']);
    }

    public function testUpdateFromPredefined()
    {
        //TODO: see BB-1134
        $this->markTestSkipped('Must be fixed in scope with BB-1134');

        $currentUserRoles = $this->currentUser->getRoles();
        $oldRoleId = $this->predefinedRole->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_update', ['id' => $oldRoleId])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('orob2b_account_frontend_account_user_role')->getValue();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'input_action'        => '',
            'orob2b_account_frontend_account_user_role' => [
                '_token' => $token,
                'label' => self::CUSTOMIZED_ROLE,
                'appendUsers' => $this->currentUser->getId(),
            ]
        ]);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $content = $crawler->html();
        $this->assertContains('Account User Role has been saved', $content);

        // Find id of new role
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-roles-grid',
            [
                'frontend-account-account-user-roles-grid[_filter][label][value]' => self::CUSTOMIZED_ROLE
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $newRoleId = $result['id'];
        $this->assertNotEquals($newRoleId, $oldRoleId);

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->find($newRoleId);

        $this->assertNotNull($role);
        $this->assertEquals(self::CUSTOMIZED_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getCurrentUser();

        // Add new role
        $this->assertCount(count($currentUserRoles) + 1, $user->getRoles());
        $this->assertEquals($user->getRole($role->getRole()), $role);
    }

    /**
     * @depends testUpdateFromPredefined
     */
    public function testIndexFromPredefined()
    {
        $response = $this->requestFrontendGrid(
            'frontend-account-account-user-roles-grid',
            [
                'frontend-account-account-user-roles-grid[_filter][label][value]' => self::CUSTOMIZED_ROLE
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->find($id);
        $this->assertFalse($role->isPredefined());
    }

    /**
     * @return AccountUserRole
     */
    protected function getPredefinedRole()
    {
        return $this->getUserRoleRepository()
            ->findOneBy(['label' => LoadAccountUserRoleData::ROLE_WITHOUT_ACCOUNT]);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUser');
    }

    /**
     * @return \OroB2B\Bundle\AccountBundle\Entity\Repository\AccountRepository
     */
    protected function getAccountRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:Account');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUserRole');
    }

    /**
     * @return AccountUser
     */
    protected function getCurrentUser()
    {
        return $this->getUserRepository()->findOneBy(['username' => OroLoadAccountUserData::AUTH_USER]);
    }
}
