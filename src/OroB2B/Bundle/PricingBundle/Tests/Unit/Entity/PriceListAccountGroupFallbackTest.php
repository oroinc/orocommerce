<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;

class PriceListAccountGroupFallbackTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListAccountGroupFallback(),
            [
                ['id', 42],
                ['accountGroup', new AccountGroup()],
                ['fallback', 1],
            ]
        );
    }
}
