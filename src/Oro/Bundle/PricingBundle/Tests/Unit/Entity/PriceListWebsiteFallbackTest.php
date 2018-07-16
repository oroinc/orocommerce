<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceListWebsiteFallbackTest extends \PHPUnit\Framework\TestCase
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
