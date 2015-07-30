<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData;

/**
 * @dbIsolation
 */
class AccountUserRoleControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
            ->findOneBy(['label' => LoadAccountUserRoleData::ROLE_WITHOUT_USER_AND_WEBSITE]);

        $this->assertNotNull($userRole);

        $id = $userRole->getId();

        $this->client->request('DELETE', $this->getUrl('orob2b_api_customer_delete_accountuserrole', ['id' => $id]));
        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);

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
