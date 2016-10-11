<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

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
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var \Oro\Bundle\AccountBundle\Entity\AccountUserRole $userRole */
        $userRole = $this->getUserRoleRepository()
            ->findOneBy(['label' => LoadAccountUserRoleData::ROLE_EMPTY]);

        $this->assertNotNull($userRole);

        $id = $userRole->getId();

        $this->assertDeleteOperation(
            $id,
            'oro_account.entity.account_user_role.class',
            'oro_account_account_user_role_index'
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
        return $this->getObjectManager()->getRepository('OroAccountBundle:AccountUserRole');
    }
}
