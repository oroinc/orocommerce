<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListWebsiteFallbackTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            new PriceListWebsiteFallback(),
            [
                ['id', 42],
                ['website', new Website()],
                ['fallback', 1],
            ]
        );
    }
}
