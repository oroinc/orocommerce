<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

class CurrencyAwarePaymentMethodTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testContextEmpty()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub([]);

        $this->assertFalse($stub->isCurrencyApplicable());
    }

    public function testNoSelectedCountries()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub([]);

        $this->assertFalse($stub->isCurrencyApplicable(['currency' => 'USD']));
    }

    public function testNotAllowed()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub(['UK']);

        $this->assertFalse($stub->isCurrencyApplicable(['currency' => 'USD']));
    }

    public function testAllowed()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub(['CAD', 'USD']);

        $this->assertTrue($stub->isCurrencyApplicable(['currency' => 'USD']));
    }

    public function testDoNotRelyOnConfigValueType()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub(['CAD', 'USD', new \stdClass()]);

        $this->assertTrue($stub->isCurrencyApplicable(['currency' => 'USD']));
    }

    public function testDoNotRelyOnContextValueType()
    {
        $stub = new CurrencyAwarePaymentMethodTraitStub(['CAD', 'USD', new \stdClass()]);

        $this->assertFalse($stub->isCurrencyApplicable(['currency' => new \stdClass()]));
    }
}
