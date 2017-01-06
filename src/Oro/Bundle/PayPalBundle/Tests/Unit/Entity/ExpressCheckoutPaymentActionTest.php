<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Entity;

use Oro\Bundle\PayPalBundle\Entity\ExpressCheckoutPaymentAction;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ExpressCheckoutPaymentActionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new ExpressCheckoutPaymentAction(), [
            ['label', 'some string'],
        ]);
    }
}
