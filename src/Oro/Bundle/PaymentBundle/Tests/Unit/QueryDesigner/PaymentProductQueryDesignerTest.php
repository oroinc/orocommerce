<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PaymentProductQueryDesignerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testMethods()
    {
        static::assertPropertyAccessors(new PaymentProductQueryDesigner(), [
            ['entity', 'string'],
            ['definition', 'string'],
        ]);
    }
}
