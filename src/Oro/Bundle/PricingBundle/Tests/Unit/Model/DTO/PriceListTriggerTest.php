<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;

class PriceListTriggerTest extends \PHPUnit_Framework_TestCase
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
    }
}
