<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserControllerTest extends AbstractUserControllerTest
{
    const NAME_PREFIX = 'NamePrefix';
    const MIDDLE_NAME = 'MiddleName';
    const NAME_SUFFIX = 'NameSuffix';
    const EMAIL = 'first@example.com';
    const FIRST_NAME = 'John';
    const LAST_NAME = 'Doe';

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
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleData',
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    /**
     * @dataProvider createDataProvider
     * @param string $email
     * @param string $password
     * @param bool   $isPasswordGenerate
     * @param bool   $isSendEmail
     * @param int    $emailsCount
     */
    public function testCreate($email, $password, $isPasswordGenerate, $isSendEmail, $emailsCount)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_account_user_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var \Oro\Bundle\CustomerBundle\Entity\Customer $account */
        $account = $this->getAccountRepository()->findOneBy([]);

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUserRole $role */
        $role = $this->getUserRoleRepository()->findOneBy(
            ['role' => CustomerUserRole::PREFIX_ROLE . LoadCustomerUserRoles::ADMINISTRATOR]
        );

        $this->assertNotNull($account);
        $this->assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_account_account_user[enabled]'] = true;
        $form['oro_account_account_user[namePrefix]'] = self::NAME_PREFIX;
        $form['oro_account_account_user[firstName]'] = self::FIRST_NAME;
        $form['oro_account_account_user[middleName]'] = self::MIDDLE_NAME;
        $form['oro_account_account_user[lastName]'] = self::LAST_NAME;
        $form['oro_account_account_user[nameSuffix]'] = self::NAME_SUFFIX;
        $form['oro_account_account_user[email]'] = $email;
        $form['oro_account_account_user[birthday]'] = date('Y-m-d');
        $form['oro_account_account_user[plainPassword][first]'] = $password;
        $form['oro_account_account_user[plainPassword][second]'] = $password;
        $form['oro_account_account_user[account]'] = $account->getId();
        $form['oro_account_account_user[passwordGenerate]'] = $isPasswordGenerate;
        $form['oro_account_account_user[sendEmail]'] = $isSendEmail;
        $form['oro_account_account_user[roles][0]']->tick();
        $form['oro_account_account_user[salesRepresentatives]'] = implode(',', [
            $this->getReference(LoadUserData::USER1)->getId(),
            $this->getReference(LoadUserData::USER2)->getId()
        ]);

        $this->client->submit($form);

        /** @var \Swift_Plugins_MessageLogger $emailLogging */
        $emailLogger = $this->getContainer()->get('swiftmailer.plugin.messagelogger');
        $emailMessages = $emailLogger->getMessages();

        $this->assertCount($emailsCount, $emailMessages);

        if ($isSendEmail) {
            $this->assertMessage($email, array_shift($emailMessages));
        }

        $crawler = $this->client->followRedirect();
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User has been saved', $crawler->html());
        $this->assertContains($this->getReference(LoadUserData::USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::USER2)->getFullName(), $result->getContent());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_account_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('account-account-user-grid', $crawler->html());
        $this->assertContains(self::FIRST_NAME, $result->getContent());
        $this->assertContains(self::LAST_NAME, $result->getContent());
        $this->assertContains(self::EMAIL, $result->getContent());
    }

    /**
     * @depends testCreate
     * @return integer
     */
    public function testUpdate()
    {
        /** @var CustomerUser $account */
        $accountUser = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUser')
            ->getRepository('OroCustomerBundle:CustomerUser')
            ->findOneBy(['email' => self::EMAIL, 'firstName' => self::FIRST_NAME, 'lastName' => self::LAST_NAME]);
        $id = $accountUser->getId();

        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_account_user_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_account_account_user[enabled]'] = false;
        $form['oro_account_account_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['oro_account_account_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['oro_account_account_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['oro_account_account_user[lastName]'] = self::UPDATED_LAST_NAME;
        $form['oro_account_account_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['oro_account_account_user[email]'] = self::UPDATED_EMAIL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User has been saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     * @param integer $id
     * @return integer
     */
    public function testView($id)
    {
        $this->client->request('GET', $this->getUrl('oro_customer_account_user_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        $this->assertContains(
            sprintf('%s - Customer Users - Customers', self::UPDATED_EMAIL),
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
            $this->getUrl('oro_customer_account_user_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
        $user = $this->getUserRepository()->find($id);
        $this->assertNotNull($user);

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUserRole $role */
        $roles = $user->getRoles();
        $role = reset($roles);
        $this->assertNotNull($role);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::UPDATED_FIRST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_LAST_NAME, $result->getContent());
        $this->assertContains(self::UPDATED_EMAIL, $result->getContent());
        $this->assertContains($user->getAccount()->getName(), $result->getContent());
        $this->assertContains($role->getLabel(), $result->getContent());
    }

    public function testGetRolesWithAccountAction()
    {
        $manager = $this->getObjectManager();

        $foreignAccount = $this->createAccount('Foreign account');
        $foreignRole = $this->createCustomerUserRole('Custom foreign role');
        $foreignRole->setAccount($foreignAccount);

        $expectedRoles[] = $this->createCustomerUserRole('Predefined test role');
        $notExpectedRoles[] = $foreignRole;
        $manager->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_roles'),
            ['_widgetContainer' => 'widget']
        );
        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent());

        // With account parameter
        $expectedRoles = $notExpectedRoles = [];
        $expectedRoles[] = $foreignRole;

        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_roles', ['accountId' => $foreignAccount->getId()])
        );

        $response = $this->client->getResponse();

        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent());
    }

    public function testGetRolesWithUserAction()
    {
        $manager = $this->getObjectManager();

        $foreignAccount = $this->createAccount('User foreign account');
        $notExpectedRoles[] = $foreignRole = $this->createCustomerUserRole('Custom user foreign role');
        $foreignRole->setAccount($foreignAccount);

        $userAccount = $this->createAccount('User account');
        $expectedRoles[] = $userRole = $this->createCustomerUserRole('Custom user role');
        $userRole->setAccount($userAccount);

        $accountUser = $this->createAccountUser('test@example.com');
        $accountUser->setAccount($userAccount);
        $accountUser->addRole($userRole);

        $expectedRoles[] = $predefinedRole = $this->createCustomerUserRole('User predefined role');
        $accountUser->addRole($predefinedRole);

        $manager->flush();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'accountUserId' => $accountUser->getId(),
                    'accountId'     => $userAccount->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent(), $accountUser);

        // Without account parameter
        $expectedRoles = $notExpectedRoles = [];
        $notExpectedRoles[] = $userRole;
        $expectedRoles[] = $predefinedRole;

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'accountUserId' => $accountUser->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent(), $accountUser);


        //with predefined error
        $errorMessage = 'Test error message';
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'accountUserId' => $accountUser->getId(),
                    'error'         => $errorMessage
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();
        $this->assertContains($errorMessage, $response->getContent());
    }

    /**
     * @param string $name
     * @return Customer
     */
    protected function createAccount($name)
    {
        $account = new Customer();
        $account->setName($name);
        $account->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($account);

        return $account;
    }

    /**
     * @param string $name
     * @return CustomerUserRole
     */
    protected function createCustomerUserRole($name)
    {
        $role = new CustomerUserRole($name);
        $role->setLabel($name);
        $role->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($role);

        return $role;
    }

    /**
     * @param string $email
     * @return CustomerUser
     */
    protected function createAccountUser($email)
    {
        $accountUser = new CustomerUser();
        $accountUser->setEmail($email);
        $accountUser->setPassword('password');
        $accountUser->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($accountUser);

        return $accountUser;
    }

    /**
     * @return Organization
     */
    protected function getDefaultOrganization()
    {
        return $this->getObjectManager()->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
    }

    /**
     * @param CustomerUserRole[] $expectedRoles
     * @param CustomerUserRole[] $notExpectedRoles
     * @param string            $content
     * @param CustomerUser|null  $accountUser
     */
    protected function assertRoles(
        array $expectedRoles,
        array $notExpectedRoles,
        $content,
        CustomerUser $accountUser = null
    ) {
        $shouldBeChecked = 0;
        /** @var CustomerUserRole $expectedRole */
        foreach ($expectedRoles as $expectedRole) {
            $this->assertContains($expectedRole->getLabel(), $content);
            if ($accountUser && $accountUser->hasRole($expectedRole)) {
                $shouldBeChecked++;
            }
        }
        $this->assertEquals($shouldBeChecked, substr_count($content, 'checked="checked"'));

        /** @var CustomerUserRole $notExpectedRole */
        foreach ($notExpectedRoles as $notExpectedRole) {
            $this->assertNotContains($notExpectedRole->getLabel(), $content);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmail()
    {
        return self::EMAIL;
    }
}
