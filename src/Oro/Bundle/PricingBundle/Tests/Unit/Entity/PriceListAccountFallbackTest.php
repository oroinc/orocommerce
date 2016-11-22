<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListAccountFallbackTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListAccountFallback(),
            [
                ['id', 42],
                ['account', new Account()],
                ['fallback', 1],
                ['website', new Website()]
            ]
        );
    }
}
