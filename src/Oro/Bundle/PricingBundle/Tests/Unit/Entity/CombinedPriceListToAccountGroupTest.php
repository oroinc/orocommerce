<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;

class CombinedPriceListToAccountGroupTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new CombinedPriceListToAccountGroup(),
            [
                ['accountGroup', new AccountGroup()]
            ]
        );
    }
}
