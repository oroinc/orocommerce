<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers'
            ]
        );
    }

    public function testDelete()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.orphan');

        $this->assertDeleteOperation(
            $customer->getId(),
            'oro_customer.entity.customer.class',
            'oro_customer_customer_index'
        );
    }
}
