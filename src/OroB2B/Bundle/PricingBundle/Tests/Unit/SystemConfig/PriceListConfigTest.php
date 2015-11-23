<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;

class PriceListConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListConfig(),
            [
                ['priceList', new PriceList()],
                ['priority', 100]
            ]
        );
    }
}
