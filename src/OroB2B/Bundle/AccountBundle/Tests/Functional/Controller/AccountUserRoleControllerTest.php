<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    const TEST_ROLE = 'Test account user role';
    const UPDATED_TEST_ROLE = 'Updated test account user role';

    /**
     * @var array
     */
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
            ]
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_account_user_role_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_account_user_role[label]'] = self::TEST_ROLE;
        $form['orob2b_account_account_user_role[privileges]'] = json_encode($this->privileges);

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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_account_user_role_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('account-account-user-roles-grid', $crawler->html());
        $this->assertContains(self::TEST_ROLE, $result->getContent());
    }

    /**
     * @depend testCreate
     * @return int
     */
    public function testUpdate()
    {
        /** @var AccountUserRole $role = */
        $role = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
            ->getRepository('OroB2BAccountBundle:AccountUserRole')
            ->findOneBy(['label' => self::TEST_ROLE]);
        $id = $role->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_account_user_role_update', ['id' => $id])
        );

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $accountUser */
        $accountUser = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);
        $account = $this->getAccountRepository()->findOneBy(['name' => 'account.orphan']);
        $accountUser->setAccount($account);
        $this->getObjectManager()->flush();

        $this->assertNotNull($accountUser);
        $this->assertContains('Add note', $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('orob2b_account_account_user_role')->getValue();
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'orob2b_account_account_user_role' => [
                '_token' => $token,
                'label' => self::UPDATED_TEST_ROLE,
                'account' => $account->getId(),
                'appendUsers' => $accountUser->getId(),
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
        $this->assertEquals(self::UPDATED_TEST_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $this->assertNotNull($user);
        $this->assertEquals($user->getRole($role->getRole()), $role);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_account_user_role_view', ['id' => $id])
        );

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        // Check datagrid
        $response = $this->client->requestGrid(
            'account-account-users-grid-view',
            [
                'account-account-users-grid-view[role]' => $id,
                'account-account-users-grid-view[_filter][email][value]' => LoadAccountUserData::EMAIL
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        /** @var AccountUser $accountUser */
        $accountUser = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);
        $result = reset($result['data']);

        $this->assertEquals($accountUser->getId(), $result['id']);
        $this->assertEquals($accountUser->getFirstName(), $result['firstName']);
        $this->assertEquals($accountUser->getLastName(), $result['lastName']);
        $this->assertEquals($accountUser->getEmail(), $result['email']);
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
}
