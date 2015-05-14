<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class GroupControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\UserAdminBundle\Tests\Functional\DataFixtures\LoadGroupData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var \OroB2B\Bundle\UserAdminBundle\Entity\Group $group */
        $group = $this->getGroupRepository()->findOneBy([]);

        $this->assertNotNull($group);
        $id = $group->getId();

        $this->client->request('DELETE', $this->getUrl('orob2b_api_user_admin_delete_frontendrole', ['id' => $id]));
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->getObjectManager()->clear();
        $group = $this->getGroupRepository()->find($id);

        $this->assertNull($group);
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
    protected function getGroupRepository()
    {
        return $this->getObjectManager()->getRepository('OroB2BUserAdminBundle:Group');
    }
}
