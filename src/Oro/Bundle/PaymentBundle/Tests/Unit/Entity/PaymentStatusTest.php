<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentStatusTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['entityIdentifier', 10],
            ['entityClass', 'Some\Class'],
            ['paymentStatus', 'pending'],
        ];

        $this->assertPropertyAccessors(new PaymentStatus(), $properties);
    }
}
