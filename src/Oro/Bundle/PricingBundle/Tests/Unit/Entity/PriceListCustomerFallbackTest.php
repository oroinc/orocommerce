<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerFallbackTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListCustomerFallback(),
            [
                ['id', 42],
                ['customer', new Customer()],
                ['fallback', 1],
                ['website', new Website()]
            ]
        );
    }
}
