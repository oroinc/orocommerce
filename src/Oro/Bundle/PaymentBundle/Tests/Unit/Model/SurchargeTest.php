<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SurchargeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['shippingAmount', 10.5],
            ['handlingAmount', 13.2],
            ['discountAmount', 5.1],
            ['insuranceAmount', 2.7],
        ];

        $this->assertPropertyAccessors(new Surcharge(), $properties);
    }
}
