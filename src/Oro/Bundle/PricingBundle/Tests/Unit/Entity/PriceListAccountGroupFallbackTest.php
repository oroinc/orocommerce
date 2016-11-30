<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
                ['website', new Website()]
            ]
        );
    }
}
