<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\DependencyInjection\Configuration;

/**
 * @dbIsolation
 */
class AccountGroupControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups'
            ]
        );
    }

    public function testDelete()
    {
        /** @var AccountGroup $entity */
        $entity = $this->getReference('account_group.group1');
        $id = $entity->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_account_delete_account_group', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    public function testDeleteAnonymousUserGroup()
    {
        $id = $this->getContainer()
            ->get('oro_config.global')
            ->get('oro_b2b_account.anonymous_account_group');

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_account_delete_account_group', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertSame(403, $result->getStatusCode());
    }
}
