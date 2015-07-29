<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller;

use Symfony\Bridge\Swiftmailer\DataCollector\MessageDataCollector;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
{
    const NAME_PREFIX = 'NamePrefix';
    const MIDDLE_NAME = 'MiddleName';
    const NAME_SUFFIX = 'NameSuffix';
    const EMAIL       = 'first@example.com';
    const FIRST_NAME  = 'John';
    const LAST_NAME   = 'Doe';

    const UPDATED_NAME_PREFIX = 'UNamePrefix';
    const UPDATED_FIRST_NAME = 'UFirstName';
    const UPDATED_MIDDLE_NAME = 'UMiddleName';
    const UPDATED_LAST_NAME = 'UpdLastName';
    const UPDATED_NAME_SUFFIX = 'UNameSuffix';
    const UPDATED_EMAIL = 'updated@example.com';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * @dataProvider createDataProvider
     * @param string $email
     * @param string $password
     * @param bool $isPasswordGenerate
     * @param bool $isSendEmail
     * @param int $emailsCount
     */
    public function testCreate($email, $password, $isPasswordGenerate, $isSendEmail, $emailsCount)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_create'));

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\Customer $customer */
        $customer = $this->getCustomerRepository()->findOneBy([]);

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->findOneBy([]);

        $this->assertNotNull($customer);
        $this->assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_customer_account_user[enabled]']               = true;
        $form['orob2b_customer_account_user[namePrefix]']            = self::NAME_PREFIX;
        $form['orob2b_customer_account_user[firstName]']             = self::FIRST_NAME;
        $form['orob2b_customer_account_user[middleName]']            = self::MIDDLE_NAME;
        $form['orob2b_customer_account_user[lastName]']              = self::LAST_NAME;
        $form['orob2b_customer_account_user[nameSuffix]']            = self::NAME_SUFFIX;
        $form['orob2b_customer_account_user[email]']                 = $email;
        $form['orob2b_customer_account_user[birthday]']              = date('Y-m-d');
        $form['orob2b_customer_account_user[plainPassword][first]']  = $password;
        $form['orob2b_customer_account_user[plainPassword][second]'] = $password;
        $form['orob2b_customer_account_user[customer]']              = $customer->getId();
        $form['orob2b_customer_account_user[passwordGenerate]']      = $isPasswordGenerate;
        $form['orob2b_customer_account_user[sendEmail]']             = $isSendEmail;
        $form['orob2b_customer_account_user[roles]']                 = [$role->getId()];

        $this->client->submit($form);

        /** @var MessageDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('swiftmailer');
        $collectedMessages = $collector->getMessages();

        $this->assertCount($emailsCount, $collectedMessages);

        if ($isSendEmail) {
            $this->assertMessage($email, array_shift($collectedMessages));
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Account User has been saved', $crawler->html());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'simple create' => [
                'email' => self::EMAIL,
                'password' => '123456',
                'isPasswordGenerate' => false,
                'isSendEmail' => false,
                'emailsCount' => 0
            ],
            'create with email and without password generator' => [
                'email' => 'second@example.com',
                'password' => '123456',
                'isPasswordGenerate' => false,
                'isSendEmail' => true,
                'emailsCount' => 1
            ],
            'create with email and password generator' => [
                'email' => 'third@example.com',
                'password' => '',
                'isPasswordGenerate' => true,
                'isSendEmail' => true,
                'emailsCount' => 1
            ]
        ];
    }

    /**
     * @param string $email
     * @param \Swift_Message $message
     */
    protected function assertMessage($email, \Swift_Message $message)
    {
        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => $email]);

        $this->assertNotNull($user);

        $this->assertInstanceOf('\Swift_Message', $message);

        $this->assertEquals($email, key($message->getTo()));
        $this->assertEquals(
            $this->getContainer()->get('oro_config.manager')->get('oro_notification.email_notification_sender_email'),
            key($message->getFrom())
        );

        $this->assertContains($email, $message->getSubject());
        $this->assertContains($email, $message->getBody());
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

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getCustomerRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BCustomerBundle:Customer');
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::FIRST_NAME, $result->getContent());
        $this->assertContains(self::LAST_NAME, $result->getContent());
        $this->assertContains(self::EMAIL, $result->getContent());
    }

    /**
     * @depend testCreate
     * @return integer
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'customer-account-user-grid',
            [
                'customer-account-user-grid[_filter][firstName][value]' => self::FIRST_NAME,
                'customer-account-user-grid[_filter][LastName][value]' => self::LAST_NAME,
                'customer-account-user-grid[_filter][email][value]' => self::EMAIL
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_customer_account_user[enabled]']    = false;
        $form['orob2b_customer_account_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['orob2b_customer_account_user[firstName]']  = self::UPDATED_FIRST_NAME;
        $form['orob2b_customer_account_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['orob2b_customer_account_user[lastName]']   = self::UPDATED_LAST_NAME;
        $form['orob2b_customer_account_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['orob2b_customer_account_user[email]']      = self::UPDATED_EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Account User has been saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     * @return integer
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('orob2b_customer_account_user_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains(
            sprintf('%s - Account Users - Customers', self::UPDATED_EMAIL),
            $content
        );

        $this->assertContains('Add attachment', $content);
        $this->assertContains('Add note', $content);
        $this->assertContains('Send email', $content);
        $this->assertContains('Add Event', $content);
        $this->assertContains('Address Book', $content);

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     */
    public function testInfo($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_customer_account_user_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->find($id);
        $this->assertNotNull($user);

        /** @var \OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole $role */
        $roles = $user->getRoles();
        $role = reset($roles);
        $this->assertNotNull($role);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::UPDATED_FIRST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_LAST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_EMAIL, $result->getContent());
        $this->assertContains($user->getCustomer()->getName(), $result->getContent());
        $this->assertContains($role->getLabel(), $result->getContent());
    }
}
