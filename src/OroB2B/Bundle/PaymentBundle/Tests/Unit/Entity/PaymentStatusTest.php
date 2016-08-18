<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentStatus;

class PaymentStatusTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['entityIdentifier', 10],
            ['entityClass', 'Some\Class'],
            ['paymentStatus', 'payment_term'],
        ];

        $this->assertPropertyAccessors(new PaymentStatus(), $properties);
    }
}
