<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CustomerControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.orphan');
        $customerId = $customer->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_customer_delete_customer', ['id' => $customerId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
