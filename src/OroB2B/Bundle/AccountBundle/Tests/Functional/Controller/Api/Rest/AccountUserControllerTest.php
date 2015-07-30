<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadAccountUserData::EMAIL]);

        $this->assertNotNull($user);
        $id = $user->getId();

        $this->client->request('DELETE', $this->getUrl('orob2b_api_customer_delete_account_user', ['id' => $id]));
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->getObjectManager()->clear();
        $user = $this->getUserRepository()->find($id);

        $this->assertNull($user);
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
}
