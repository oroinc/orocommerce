<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;

/**
 * @dbIsolation
 */
class CustomerUserRoleControllerTest extends WebTestCase
{
    const TEST_ROLE = 'Test customer user role';
    const UPDATED_TEST_ROLE = 'Updated test customer user role';

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
                    'id' => 'entity:Oro\Bundle\CustomerBundle\Entity\Customer',
                    'name' => 'oro.customer.customer.entity_label',
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
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers',
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData'
            ]
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_role_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_customer_customer_user_role[label]'] = self::TEST_ROLE;
        $form['oro_customer_customer_user_role[privileges]'] = json_encode($this->privileges);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User Role has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_customer_user_role_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('customer-customer-user-roles-grid', $crawler->html());
        $this->assertContains(self::TEST_ROLE, $result->getContent());
    }

    /**
     * @depend testCreate
     * @return int
     */
    public function testUpdate()
    {
        /** @var CustomerUserRole $role = */
        $role = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUserRole')
            ->getRepository('OroCustomerBundle:CustomerUserRole')
            ->findOneBy(['label' => self::TEST_ROLE]);
        $id = $role->getId();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_role_update', ['id' => $id])
        );

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $customerUser */
        $customerUser = $this->getUserRepository()->findOneBy(['email' => LoadCustomerUserData::EMAIL]);
        $customer = $this->getCustomerRepository()->findOneBy(['name' => 'customer.orphan']);
        $customerUser->setCustomer($customer);
        $this->getObjectManager()->flush();

        $this->assertNotNull($customerUser);
        $this->assertContains('Add note', $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();

        $token = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('oro_customer_customer_user_role')->getValue();
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), [
            'oro_customer_customer_user_role' => [
                '_token' => $token,
                'label' => self::UPDATED_TEST_ROLE,
                'selfManaged' => true,
                'customer' => $customer->getId(),
                'appendUsers' => $customerUser->getId(),
                'privileges' => json_encode($this->privileges),
            ]
        ]);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $crawler->html();
        $this->assertContains('Customer User Role has been saved', $content);

        $this->getObjectManager()->clear();

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUserRole $role */
        $role = $this->getUserRoleRepository()->find($id);

        $this->assertNotNull($role);
        $this->assertEquals(self::UPDATED_TEST_ROLE, $role->getLabel());
        $this->assertNotEmpty($role->getRole());

        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadCustomerUserData::EMAIL]);

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
        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_role_view', ['id' => $id])
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 200);

        $this->assertEquals(4, substr_count($response->getContent(), '[Quote]'));
        $this->assertContains('Audit history for Customer User', $response->getContent());
        $this->assertNotContains('Access system information', $response->getContent());

        // Check datagrid
        $response = $this->client->requestGrid(
            'customer-customer-users-grid-view',
            [
                'customer-customer-users-grid-view[role]' => $id,
                'customer-customer-users-grid-view[_filter][email][value]' => LoadCustomerUserData::EMAIL
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getUserRepository()->findOneBy(['email' => LoadCustomerUserData::EMAIL]);
        $result = reset($result['data']);

        $this->assertEquals($customerUser->getId(), $result['id']);
        $this->assertEquals($customerUser->getFirstName(), $result['firstName']);
        $this->assertEquals($customerUser->getLastName(), $result['lastName']);
        $this->assertEquals($customerUser->getEmail(), $result['email']);
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:CustomerUser');
    }

    /**
     * @return \Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository
     */
    protected function getCustomerRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:Customer');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:CustomerUserRole');
    }
}
