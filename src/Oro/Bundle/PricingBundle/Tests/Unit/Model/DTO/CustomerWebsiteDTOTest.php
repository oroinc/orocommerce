<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerWebsiteDTOTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $website = new Website();
        $customer = new Customer();
        $object = new CustomerWebsiteDTO($customer, $website);

        $this->assertSame($website, $object->getWebsite());
        $this->assertSame($customer, $object->getCustomer());
    }
}
