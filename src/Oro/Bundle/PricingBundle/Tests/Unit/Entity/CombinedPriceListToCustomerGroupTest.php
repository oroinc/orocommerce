<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CombinedPriceListToCustomerGroupTest extends \PHPUnit\Framework\TestCase
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
