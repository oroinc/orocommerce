<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;

/**
 * @dbIsolation
 */
class AjaxCustomerUserControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
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
        $user = $this->getUserRepository()->findOneBy(['email' => LoadCustomerUserData::EMAIL]);

        $this->assertNotNull($user);

        $id = $user->getId();

        $this->client->request(
            'GET',
            $this->getUrl('oro_customer_customer_user_get_customer', ['id' => $id])
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('customerId', $data);

        $customerId = $user->getCustomer() ? $user->getCustomer()->getId() : null;

        $this->assertEquals($data['customerId'], $customerId);
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
}
