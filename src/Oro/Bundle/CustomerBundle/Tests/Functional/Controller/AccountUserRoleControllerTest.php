<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

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
                    'id' => 'action:oro_order_address_billing_allow_manual',
                    'name' => 'oro.order.security.permission.address_billing_allow_manual',
                ],
                'permissions' => [],
            ],
        ],
        'entity' => [
            0 => [
                'identity' => [
                    'id' => 'entity:Oro\Bundle\CustomerBundle\Entity\Account',
                    'name' => 'oro.customer.account.entity_label',
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
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
            ]
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_account_user_role_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_account_account_user_role[label]'] = self::TEST_ROLE;
        $form['oro_account_account_user_role[privileges]'] = json_encode($this->privileges);

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
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_account_user_role_index'));
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
            ->getManagerForClass('OroCustomerBundle:AccountUserRole')
            ->getRepository('OroCustomerBundle:AccountUserRole')
            ->findOneBy(['label' => self::TEST_ROLE]);
        $id = $role->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_account_account_user_role_update', ['id' => $id])
        );

        /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUser $accountUser */
        $accountUser = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);
        $account = $this->getAccountRepository()->findOneBy(['name' => 'account.orphan']);
        $accountUser->setAccount($account);
        $this->getObjectManager()->flush();

        $this->assertNotNull($accountUser);
        $this->assertContains('Add note', $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('oro_account_account_user_role')->getValue();
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'oro_account_account_user_role' => [
                '_token' => $token,
                'label' => self::UPDATED_TEST_ROLE,
                'selfManaged' => true,
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

        /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->find($id);

        $this->assertNotNull($role);
        $this->assertEquals(self::UPDATED_TEST_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $this->assertNotNull($user);
        $this->assertEquals($user->getRole($role->getRole()), $role);

        $this->assertTrue($role->isSelfManaged());

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
            $this->getUrl('oro_account_account_user_role_view', ['id' => $id])
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUser');
    }

    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository
     */
    protected function getAccountRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:Account');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUserRole');
    }
}
