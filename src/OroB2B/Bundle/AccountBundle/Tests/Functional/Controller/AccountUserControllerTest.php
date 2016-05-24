<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadUserData;

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

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadUserData'
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_account_user_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var \OroB2B\Bundle\AccountBundle\Entity\Account $account */
        $account = $this->getAccountRepository()->findOneBy([]);

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
        $role = $this->getUserRoleRepository()->findOneBy(
            ['role' => AccountUserRole::PREFIX_ROLE . LoadAccountUserRoles::ADMINISTRATOR]
        );

        $this->assertNotNull($account);
        $this->assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_account_user[enabled]'] = true;
        $form['orob2b_account_account_user[namePrefix]'] = self::NAME_PREFIX;
        $form['orob2b_account_account_user[firstName]'] = self::FIRST_NAME;
        $form['orob2b_account_account_user[middleName]'] = self::MIDDLE_NAME;
        $form['orob2b_account_account_user[lastName]'] = self::LAST_NAME;
        $form['orob2b_account_account_user[nameSuffix]'] = self::NAME_SUFFIX;
        $form['orob2b_account_account_user[email]'] = $email;
        $form['orob2b_account_account_user[birthday]'] = date('Y-m-d');
        $form['orob2b_account_account_user[plainPassword][first]'] = $password;
        $form['orob2b_account_account_user[plainPassword][second]'] = $password;
        $form['orob2b_account_account_user[account]'] = $account->getId();
        $form['orob2b_account_account_user[passwordGenerate]'] = $isPasswordGenerate;
        $form['orob2b_account_account_user[sendEmail]'] = $isSendEmail;
        $form['orob2b_account_account_user[roles][0]']->tick();
        $form['orob2b_account_account_user[salesRepresentatives]'] = implode(',', [
            $this->getReference(LoadUserData::USER1)->getId(),
            $this->getReference(LoadUserData::USER2)->getId()
        ]);

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
        $this->assertContains($this->getReference(LoadUserData::USER1)->getFullName(), $result->getContent());
        $this->assertContains($this->getReference(LoadUserData::USER2)->getFullName(), $result->getContent());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $account = $this->getAccountRepository()->findOneBy([]);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_account_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('account-account-user-grid', $crawler->html());
        $this->assertContains(self::FIRST_NAME, $result->getContent());
        $this->assertContains(self::LAST_NAME, $result->getContent());
        $this->assertContains(self::EMAIL, $result->getContent());
        $this->assertContains($account->getName(), $result->getContent());
    }

    /**
     * @depends testCreate
     * @return integer
     */
    public function testUpdate()
    {
        /** @var AccountUser $account */
        $accountUser = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:AccountUser')
            ->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['email' => self::EMAIL, 'firstName' => self::FIRST_NAME, 'lastName' => self::LAST_NAME]);
        $id = $accountUser->getId();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_account_account_user_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_account_account_user[enabled]'] = false;
        $form['orob2b_account_account_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['orob2b_account_account_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['orob2b_account_account_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['orob2b_account_account_user[lastName]'] = self::UPDATED_LAST_NAME;
        $form['orob2b_account_account_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['orob2b_account_account_user[email]'] = self::UPDATED_EMAIL;

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
        $this->client->request('GET', $this->getUrl('orob2b_account_account_user_view', ['id' => $id]));

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
            $this->getUrl('orob2b_account_account_user_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->find($id);
        $this->assertNotNull($user);

        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $role */
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
        $foreignRole = $this->createAccountUserRole('Custom foreign role');
        $foreignRole->setAccount($foreignAccount);

        $expectedRoles[] = $this->createAccountUserRole('Predefined test role');
        $notExpectedRoles[] = $foreignRole;
        $manager->flush();

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_account_user_roles'),
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
            $this->getUrl('orob2b_account_account_user_roles', ['accountId' => $foreignAccount->getId()])
        );

        $response = $this->client->getResponse();

        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent());
    }

    public function testGetRolesWithUserAction()
    {
        $manager = $this->getObjectManager();

        $foreignAccount = $this->createAccount('User foreign account');
        $notExpectedRoles[] = $foreignRole = $this->createAccountUserRole('Custom user foreign role');
        $foreignRole->setAccount($foreignAccount);

        $userAccount = $this->createAccount('User account');
        $expectedRoles[] = $userRole = $this->createAccountUserRole('Custom user role');
        $userRole->setAccount($userAccount);

        $accountUser = $this->createAccountUser('test@example.com');
        $accountUser->setAccount($userAccount);
        $accountUser->addRole($userRole);

        $expectedRoles[] = $predefinedRole = $this->createAccountUserRole('User predefined role');
        $accountUser->addRole($predefinedRole);

        $manager->flush();

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_account_user_roles',
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
                'orob2b_account_account_user_roles',
                [
                    'accountUserId' => $accountUser->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent(), $accountUser);
    }

    /**
     * @param string $name
     * @return Account
     */
    protected function createAccount($name)
    {
        $account = new Account();
        $account->setName($name);
        $account->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($account);

        return $account;
    }

    /**
     * @param string $name
     * @return AccountUserRole
     */
    protected function createAccountUserRole($name)
    {
        $role = new AccountUserRole($name);
        $role->setLabel($name);
        $role->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($role);

        return $role;
    }

    /**
     * @param string $email
     * @return AccountUser
     */
    protected function createAccountUser($email)
    {
        $accountUser = new AccountUser();
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
     * @param AccountUserRole[] $expectedRoles
     * @param AccountUserRole[] $notExpectedRoles
     * @param string            $content
     * @param AccountUser|null  $accountUser
     */
    protected function assertRoles(
        array $expectedRoles,
        array $notExpectedRoles,
        $content,
        AccountUser $accountUser = null
    ) {
        $shouldBeChecked = 0;
        /** @var AccountUserRole $expectedRole */
        foreach ($expectedRoles as $expectedRole) {
            $this->assertContains($expectedRole->getLabel(), $content);
            if ($accountUser && $accountUser->hasRole($expectedRole)) {
                $shouldBeChecked++;
            }
        }
        $this->assertEquals($shouldBeChecked, substr_count($content, 'checked="checked"'));

        /** @var AccountUserRole $notExpectedRole */
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
