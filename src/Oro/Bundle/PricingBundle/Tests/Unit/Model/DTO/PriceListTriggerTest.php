<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;

class PriceListTriggerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProduct()
    {
        $data = [
            1001 => [
                2002,
                3003,
            ]
        ];

        $trigger = new PriceListTrigger($data);

        $this->assertSame($data, $trigger->getProducts());
        $this->assertEquals([1001], $trigger->getPriceListIds());
    }
}
