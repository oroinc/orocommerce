<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;

class CombinedPriceListToCustomerGroupTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListToCustomerGroup(),
            [
                ['customerGroup', new CustomerGroup()]
            ]
        );
    }
}
