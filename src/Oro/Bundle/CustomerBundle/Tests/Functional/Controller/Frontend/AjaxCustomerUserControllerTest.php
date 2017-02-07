<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadLoginCustomerUserData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class AjaxCustomerUserControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadLoginCustomerUserData::AUTH_USER, LoadLoginCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleData'
            ]
        );
    }

    public function testGetCustomerIdAction()
    {
        /** @var CustomerUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => 'customer.user2@test.com']);
        $this->assertNotNull($user);
        $id = $user->getId();
        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_frontend_customer_user_get_customer', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('customerId', $data);
        $customerId = $user->getCustomer() ? $user->getCustomer()->getId() : null;
        $this->assertEquals($data['customerId'], $customerId);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:CustomerUser');
    }
}
