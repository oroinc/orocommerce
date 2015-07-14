<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    const TEST_ROLE = 'Test account user role';
    const UPDATED_TEST_ROLE = 'Updated test account user role';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
            ]
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_role_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_customer_account_user_role[label]'] = self::TEST_ROLE;

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
        $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_role_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::TEST_ROLE, $result->getContent());
    }

    /**
     * @depend testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'customer-account-user-roles-grid',
            [
                'customer-account-user-roles-grid[_filter][label][value]' => self::TEST_ROLE
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_customer_account_user_role_update', ['id' => $id])
        );

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUser $accountUser */
        $accountUser = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $this->assertNotNull($accountUser);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_customer_account_user_role[label]'] = self::UPDATED_TEST_ROLE;
        $form['orob2b_customer_account_user_role[appendUsers]'] = $accountUser->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $crawler->html();
        $this->assertContains('Account User Role has been saved', $content);
        $this->assertContains('Add note', $content);

        $this->getObjectManager()->clear();

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->find($id);

        $this->assertNotNull($role);
        $this->assertEquals(self::UPDATED_TEST_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $this->assertNotNull($user);
        $this->assertNotNull($user->getRole($role->getRole()));
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
        return $this->getObjectManager()->getRepository('OroB2BCustomerBundle:AccountUser');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BCustomerBundle:AccountUserRole');
    }
}
