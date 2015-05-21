<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CustomerGroupControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups'
            ]
        );
    }

    public function testDelete()
    {
        /** @var CustomerGroup $entity */
        $entity = $this->getReference('customer_group.group1');
        $id = $entity->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_customer_delete_customer_group', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
