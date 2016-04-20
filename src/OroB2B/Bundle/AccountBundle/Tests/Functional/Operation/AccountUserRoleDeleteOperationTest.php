<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

/**
 * @dbIsolation
 */
class AccountUserRoleDeleteOperationTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var \OroB2B\Bundle\AccountBundle\Entity\AccountUserRole $userRole */
        $userRole = $this->getUserRoleRepository()
            ->findOneBy(['label' => LoadAccountUserRoleData::ROLE_EMPTY]);

        $this->assertNotNull($userRole);

        $id = $userRole->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $id,
            $this->getContainer()->getParameter('orob2b_account.entity.account_user_role.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_account_account_user_role_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->getObjectManager()->clear();
        $userRole = $this->getUserRoleRepository()->find($id);

        $this->assertNull($userRole);
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
    protected function getUserRoleRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BAccountBundle:AccountUserRole');
    }
}
