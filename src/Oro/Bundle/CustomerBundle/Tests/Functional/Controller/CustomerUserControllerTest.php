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
class CustomerUserControllerTest extends AbstractUserControllerTest
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
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
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
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var \Oro\Bundle\CustomerBundle\Entity\Customer $customer */
        $customer = $this->getCustomerRepository()->findOneBy([]);

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUserRole $role */
        $role = $this->getUserRoleRepository()->findOneBy(
            ['role' => CustomerUserRole::PREFIX_ROLE . LoadCustomerUserRoles::ADMINISTRATOR]
        );

        $this->assertNotNull($customer);
        $this->assertNotNull($role);

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_customer_customer_user[enabled]'] = true;
        $form['oro_customer_customer_user[namePrefix]'] = self::NAME_PREFIX;
        $form['oro_customer_customer_user[firstName]'] = self::FIRST_NAME;
        $form['oro_customer_customer_user[middleName]'] = self::MIDDLE_NAME;
        $form['oro_customer_customer_user[lastName]'] = self::LAST_NAME;
        $form['oro_customer_customer_user[nameSuffix]'] = self::NAME_SUFFIX;
        $form['oro_customer_customer_user[email]'] = $email;
        $form['oro_customer_customer_user[birthday]'] = date('Y-m-d');
        $form['oro_customer_customer_user[plainPassword][first]'] = $password;
        $form['oro_customer_customer_user[plainPassword][second]'] = $password;
        $form['oro_customer_customer_user[customer]'] = $customer->getId();
        $form['oro_customer_customer_user[passwordGenerate]'] = $isPasswordGenerate;
        $form['oro_customer_customer_user[sendEmail]'] = $isSendEmail;
        $form['oro_customer_customer_user[roles][0]']->tick();
        $form['oro_customer_customer_user[salesRepresentatives]'] = implode(',', [
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
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('customer-customer-user-grid', $crawler->html());
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
        /** @var CustomerUser $customer */
        $customerUser = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUser')
            ->getRepository('OroCustomerBundle:CustomerUser')
            ->findOneBy(['email' => self::EMAIL, 'firstName' => self::FIRST_NAME, 'lastName' => self::LAST_NAME]);
        $id = $customerUser->getId();

        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_customer_customer_user[enabled]'] = false;
        $form['oro_customer_customer_user[namePrefix]'] = self::UPDATED_NAME_PREFIX;
        $form['oro_customer_customer_user[firstName]'] = self::UPDATED_FIRST_NAME;
        $form['oro_customer_customer_user[middleName]'] = self::UPDATED_MIDDLE_NAME;
        $form['oro_customer_customer_user[lastName]'] = self::UPDATED_LAST_NAME;
        $form['oro_customer_customer_user[nameSuffix]'] = self::UPDATED_NAME_SUFFIX;
        $form['oro_customer_customer_user[email]'] = self::UPDATED_EMAIL;

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
        $this->client->request('GET', $this->getUrl('oro_customer_customer_user_view', ['id' => $id]));

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
            $this->getUrl('oro_customer_customer_user_info', ['id' => $id]),
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
        $this->assertContains($user->getCustomer()->getName(), $result->getContent());
        $this->assertContains($role->getLabel(), $result->getContent());
    }

    public function testGetRolesWithCustomerAction()
    {
        $manager = $this->getObjectManager();

        $foreignCustomer = $this->createCustomer('Foreign customer');
        $foreignRole = $this->createCustomerUserRole('Custom foreign role');
        $foreignRole->setCustomer($foreignCustomer);

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

        // With customer parameter
        $expectedRoles = $notExpectedRoles = [];
        $expectedRoles[] = $foreignRole;

        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_roles', ['customerId' => $foreignCustomer->getId()])
        );

        $response = $this->client->getResponse();

        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent());
    }

    public function testGetRolesWithUserAction()
    {
        $manager = $this->getObjectManager();

        $foreignCustomer = $this->createCustomer('User foreign customer');
        $notExpectedRoles[] = $foreignRole = $this->createCustomerUserRole('Custom user foreign role');
        $foreignRole->setCustomer($foreignCustomer);

        $userCustomer = $this->createCustomer('User customer');
        $expectedRoles[] = $userRole = $this->createCustomerUserRole('Custom user role');
        $userRole->setCustomer($userCustomer);

        $customerUser = $this->createCustomerUser('test@example.com');
        $customerUser->setCustomer($userCustomer);
        $customerUser->addRole($userRole);

        $expectedRoles[] = $predefinedRole = $this->createCustomerUserRole('User predefined role');
        $customerUser->addRole($predefinedRole);

        $manager->flush();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'customerUserId' => $customerUser->getId(),
                    'customerId'     => $userCustomer->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent(), $customerUser);

        // Without customer parameter
        $expectedRoles = $notExpectedRoles = [];
        $notExpectedRoles[] = $userRole;
        $expectedRoles[] = $predefinedRole;

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'customerUserId' => $customerUser->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );

        $response = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $this->assertRoles($expectedRoles, $notExpectedRoles, $response->getContent(), $customerUser);


        //with predefined error
        $errorMessage = 'Test error message';
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_customer_user_roles',
                [
                    'customerUserId' => $customerUser->getId(),
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
    protected function createCustomer($name)
    {
        $customer = new Customer();
        $customer->setName($name);
        $customer->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($customer);

        return $customer;
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
    protected function createCustomerUser($email)
    {
        $customerUser = new CustomerUser();
        $customerUser->setEmail($email);
        $customerUser->setPassword('password');
        $customerUser->setOrganization($this->getDefaultOrganization());
        $this->getObjectManager()->persist($customerUser);

        return $customerUser;
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
     * @param CustomerUser|null  $customerUser
     */
    protected function assertRoles(
        array $expectedRoles,
        array $notExpectedRoles,
        $content,
        CustomerUser $customerUser = null
    ) {
        $shouldBeChecked = 0;
        /** @var CustomerUserRole $expectedRole */
        foreach ($expectedRoles as $expectedRole) {
            $this->assertContains($expectedRole->getLabel(), $content);
            if ($customerUser && $customerUser->hasRole($expectedRole)) {
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
