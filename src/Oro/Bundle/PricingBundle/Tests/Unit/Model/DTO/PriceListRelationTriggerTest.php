<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceListRelationTriggerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListRelationTrigger(),
            [
                ['website', new Website()],
                ['customer', new Customer()],
                ['customerGroup', new CustomerGroup()],
                ['force', true],
            ]
        );
    }

    public function testToArray()
    {
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn(1);
        /** @var Customer|\PHPUnit_Framework_MockObject_MockObject $customer */
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(1);
        /** @var CustomerGroup|\PHPUnit_Framework_MockObject_MockObject $customerGroup */
        $customerGroup = $this->createMock(CustomerGroup::class);
        $customerGroup->method('getId')->willReturn(1);
        $trigger = new PriceListRelationTrigger();
        $trigger->setWebsite($website)
            ->setCustomer($customer)
            ->setCustomerGroup($customerGroup);

        $this->assertEquals(
            [
                'website' => 1,
                'customer' => 1,
                'customerGroup' => 1,
                'force' => false,
            ],
            $trigger->toArray()
        );
    }
}
