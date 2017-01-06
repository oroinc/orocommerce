<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Entity;

use Oro\Bundle\PayPalBundle\Entity\CreditCardTypes;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CreditCardTypesTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new CreditCardTypes(), [
            ['label', 'some string'],
        ]);
    }
}
