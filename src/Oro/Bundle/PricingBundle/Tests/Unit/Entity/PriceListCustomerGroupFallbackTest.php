<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerGroupFallbackTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListCustomerGroupFallback(),
            [
                ['id', 42],
                ['customerGroup', new CustomerGroup()],
                ['fallback', 1],
                ['website', new Website()]
            ]
        );
    }
}
