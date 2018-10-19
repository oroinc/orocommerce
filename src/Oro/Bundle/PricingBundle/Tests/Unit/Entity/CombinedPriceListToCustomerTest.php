<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CombinedPriceListToCustomerTest extends \PHPUnit\Framework\TestCase
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
