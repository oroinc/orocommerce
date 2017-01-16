<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Entity;

use Oro\Bundle\PayPalBundle\Entity\CreditCardPaymentAction;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CreditCartPaymentActionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new CreditCardPaymentAction(), [
            ['id', 1],
            ['label', 'some string'],
        ]);

        static::assertPropertyCollection(
            new CreditCardPaymentAction(),
            'payPalSettings',
            new PayPalSettings()
        );
    }
}
