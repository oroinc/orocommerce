<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PaymentBundle\Model\Surcharge;

class SurchargeTest extends \PHPUnit_Framework_TestCase
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
