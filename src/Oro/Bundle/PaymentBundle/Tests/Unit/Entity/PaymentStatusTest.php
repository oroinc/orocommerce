<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentStatusTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 1],
            ['entityIdentifier', 10],
            ['entityClass', 'Some\Class'],
            ['paymentStatus', 'pending'],
            ['forced', true],
            ['updatedAt', new \DateTime('now')],
        ];

        self::assertPropertyAccessors(new PaymentStatus(), $properties);
    }
}
