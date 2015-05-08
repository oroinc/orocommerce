<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class UserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadUserData'
            ]
        );
    }

    /**
     * @return integer
     */
    public function testEnableAndDisable()
    {
        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\User $user */
        $user = $this->getUserRepository()->findOneBy(['email' => LoadUserData::EMAIL]);
        $id = $user->getId();

        $this->assertNotNull($user);
        $this->assertTrue($user->isEnabled());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_user_admin_disable_frontenduser', ['id' => $id])
        );
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 200);

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertFalse($user->isEnabled());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_user_admin_enable_frontenduser', ['id' => $id])
        );
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 200);

        $this->getObjectManager()->clear();

        $user = $this->getUserRepository()->find($id);
        $this->assertTrue($user->isEnabled());

        return $id;
    }

    /**
     * @depends testEnableAndDisable
     * @param integer $id
     */
    public function testDelete($id)
    {
        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\User $user */
        $user = $this->getUserRepository()->find($id);

        $this->assertNotNull($user);
        $id = $user->getId();

        $this->client->request('DELETE', $this->getUrl('orob2b_api_user_admin_delete_frontenduser', ['id' => $id]));
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
        return $this->getObjectManager()->getRepository('OroB2BUserAdminBundle:User');
    }
}
