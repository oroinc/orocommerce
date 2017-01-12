<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Entity;

use Oro\Bundle\PayPalBundle\Entity\CreditCardType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CreditCardTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new CreditCardType(), [
            ['id', 1],
            ['label', 'some string'],
        ]);
    }
}
