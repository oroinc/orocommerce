<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;

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

    public function testConstruct()
    {
        $config = new PriceListConfig();
        $this->assertNull($config->getPriceList());
        $this->assertNull($config->getPriority());

        $priceList = new PriceList();
        $priority = 100;

        $config = new PriceListConfig($priceList, $priority);
        $this->assertEquals($priceList, $config->getPriceList());
        $this->assertEquals($priority, $config->getPriority());
    }
}
