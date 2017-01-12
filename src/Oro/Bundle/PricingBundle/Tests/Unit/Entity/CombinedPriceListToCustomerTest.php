<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;

class CombinedPriceListToCustomerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListToCustomer(),
            [
                ['customer', new Customer()]
            ]
        );
    }
}
