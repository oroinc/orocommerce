<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as OroLoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    const PREDEFINED_ROLE = 'Test predefined role';
    const CUSTOMIZED_ROLE = 'Test customized role';
    const ACCOUNT_ROLE = 'Test account user role';
    const ACCOUNT_UPDATED_ROLE = 'Updated test account user role';

    protected $privileges = [
        'action' => [
            0 => [
                'identity' => [
                    'id' => 'action:orob2b_order_address_billing_allow_manual',
                    'name' => 'orob2b.order.security.permission.address_billing_allow_manual',
                ],
                'permissions' => [],
            ],
        ],
        'entity' => [
            0 => [
                'identity' => [
                    'id' => 'entity:OroB2B\Bundle\AccountBundle\Entity\Account',
                    'name' => 'orob2b.account.entity_label',
                ],
                'permissions' => [],
            ],
        ],
    ];

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

        $this->warmUpAces();
    }

    protected function warmUpAces()
    {
        $classes = [
            $this->getContainer()->getParameter('orob2b_account.entity.account_user.class'),
            $this->getContainer()->getParameter('orob2b_account.entity.account_user_role.class'),
        ];

        /** @var AclManager $aclManager */
        $aclManager = $this->getContainer()->get('oro_security.acl.manager');
        $extension = $aclManager->getExtensionSelector()->select('entity:(root)');

        foreach ($classes as $class) {
            $aclManager->setPermission(
                $aclManager->getSid(ObjectIdentityFactory::ROOT_IDENTITY_TYPE),
                $aclManager->getOid(
                    'entity:' . $class
                ),
                $extension->getMaskBuilder('VIEW')->getMask('MASK_VIEW_SYSTEM')
            );
        }
        $aclManager->flush();
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_role_create'));

        $form = $crawler->selectButton('Create')->form();
        $form['orob2b_account_frontend_account_user_role[label]'] = self::ACCOUNT_ROLE;
        $form['orob2b_account_frontend_account_user_role[privileges]'] = json_encode($this->privileges);

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

        $response = $this->client->requestGrid('frontend-account-account-user-roles-grid');

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertContains(LoadAccountUserRoleData::ROLE_WITH_ACCOUNT_USER, $response->getContent());
        $this->assertContains(self::ACCOUNT_ROLE, $response->getContent());
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
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

        $form = $crawler->selectButton('Save')->form();

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('orob2b_account_frontend_account_user_role')->getValue();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'orob2b_account_frontend_account_user_role' => [
                '_token' => $token,
                'label' => self::ACCOUNT_UPDATED_ROLE,
                'appendUsers' => $this->currentUser->getId(),
                'privileges' => json_encode($this->privileges),
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

        $this->assertEquals($role, $user->getRole($role->getRole()));

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

        $response = $this->client->requestGrid(
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
        $this->assertContains($this->currentUser->getFullName(), $result['fullName']);
        $this->assertContains($this->currentUser->getEmail(), $result['email']);
        $this->assertEquals(
            $this->currentUser->isEnabled() && $this->currentUser->isConfirmed() ? 'Active' : 'Inactive',
            trim($result['status'])
        );
    }

    /**
     * @depends testView
     */
    public function testUpdateFromPredefined()
    {
        $currentUserRoles = $this->currentUser->getRoles();
        $oldRoleId = $this->predefinedRole->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_update', ['id' => $oldRoleId])
        );

        $form = $crawler->selectButton('Save')->form();
        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('orob2b_account_frontend_account_user_role')->getValue();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'orob2b_account_frontend_account_user_role' => [
                '_token' => $token,
                'label' => self::CUSTOMIZED_ROLE,
                'appendUsers' => $this->currentUser->getId(),
                'privileges' => json_encode($this->privileges),
            ]
        ]);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $content = $crawler->html();
        $this->assertContains('Account User Role has been saved', $content);

        // Find id of new role
        $response = $this->client->requestGrid(
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
        $response = $this->client->requestGrid(
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

    public function testDisplaySelfManagedPublicRoles()
    {
        $this->client->request('GET', $this->getUrl('orob2b_account_frontend_account_user_role_index'));

        $response = $this->client->requestGrid(
            'frontend-account-account-user-roles-grid'
        );

        $result = $this->getJsonResponseContent($response, 200);

        $visibleRoleIds = array_map(
            function (array $row) {
                return $row['id'];
            },
            $result['data']
        );

        // invisible role not self managed role (self_managed = false and public = true)
        $this->assertNotContains(
            $this->getReference(LoadAccountUserRoleData::ROLE_NOT_SELF_MANAGED)->getId(),
            $visibleRoleIds
        );

        // visible not self managed role (self_managed = true and public = true)
        $this->assertContains(
            $this->getReference(LoadAccountUserRoleData::ROLE_SELF_MANAGED)->getId(),
            $visibleRoleIds
        );

        // invisible not public role (self_managed = true and public = false)
        $this->assertNotContains(
            $this->getReference(LoadAccountUserRoleData::ROLE_NOT_PUBLIC)->getId(),
            $visibleRoleIds
        );
    }

    public function testShouldNotAllowViewAndUpdateNotSelfManagedRole()
    {
        $notSelfManagedRole = $this->getReference(LoadAccountUserRoleData::ROLE_NOT_SELF_MANAGED);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_view', ['id' => $notSelfManagedRole->getId()])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_update', ['id' => $notSelfManagedRole->getId()])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testShouldNotAllowViewAndUpdateNotPublicRole()
    {
        $notPublicRole = $this->getReference(LoadAccountUserRoleData::ROLE_NOT_PUBLIC);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_view', ['id' => $notPublicRole->getId()])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_frontend_account_user_role_update', ['id' => $notPublicRole->getId()])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    /**
     * @return AccountUserRole
     */
    protected function getPredefinedRole()
    {
        return $this->getUserRoleRepository()
            ->findOneBy(['label' => LoadAccountUserRoleData::ROLE_EMPTY]);
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
