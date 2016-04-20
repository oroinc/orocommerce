<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserDeleteOperationTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

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

        $this->assertExecuteOperation(
            'DELETE',
            $id,
            $this->getContainer()->getParameter('orob2b_account.entity.account_user.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_account_account_user_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

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
